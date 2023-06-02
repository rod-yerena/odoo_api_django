<?php 
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

    // Check if _PS_ADMIN_DIR_ is defined
    if (!defined('_PS_ADMIN_DIR_')) {
        // if _PS_ADMIN_DIR_ is not defined, define.
        define('_PS_ADMIN_DIR_', getcwd());
    }

    // Setup connection with config.inc.php (required for database connection, ...)
    include(_PS_ADMIN_DIR_.'/config/config.inc.php');


  // URL of your Django API
$url = 'http://192.168.1.65:8087/polls/getAllProducts';

// Initialize cURL
$ch = curl_init();

// Set cURL options
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
echo 'Request URL: ' . curl_getinfo($ch, CURLINFO_EFFECTIVE_URL) . '<br>';


// Execute the request
$response = curl_exec($ch);

// Check for errors
if (curl_errno($ch)) {
    echo 'Error: ' . curl_error($ch);
}

// Close cURL
curl_close($ch);

// Process the response
if ($response) {
    // Decode the JSON response
    $data = json_decode($response, true);

    // Access the data
    if (isset($data['productos'])) {
        $productos = $data['productos'];

        // Loop through the products
        foreach ($productos as $producto) {
            // Access individual product properties
            $productId = $producto['id'];
            $productName = $producto['name_product'];
            $productPrice = $producto['price_product'];
            $productDescription = $producto['descrip_product'];
            $productImage = $producto['img_product'];
            $productStock = $producto['stock_product'];


            addProduct('1234567891231', 'videojuego-test', $productName, 10, $productDescription, array(                                  // Product features (array)
                array("name" => "Color", "value" => "Red"),
                array("name" => "Height", "value" => "200cm"),
            ), $productPrice, $productImage, 1, array(1, 5)  );

        }
    } else {
        echo "No products found.";
    }
} else {
    echo "Error: Empty response.";
}



   function addProduct($ean13, $ref, $name, $qty, $text, $features, $price, $imgUrl, $catDef, $catAll) {
    $product = new Product();              // Create new product in prestashop
    $product->ean13 = $ean13;
    $product->reference = $ref;
    $product->name = utf8_encode($name);
    $product->description = htmlspecialchars($text);
    $product->id_category_default = $catDef;
    $product->redirect_type = '301';
    $product->price = number_format($price, 6, '.', '');
    $product->minimal_quantity = 1;
    $product->show_price = 1;
    $product->on_sale = 0;
    $product->online_only = 0;
    $product->meta_description = '';
    $product->link_rewrite = Tools::str2url($name); // Contribution credits: mfdenis
    $product->add();                        // Submit new product
    StockAvailable::setQuantity($product->id, null, $qty); // id_product, id_product_attribute, quantity
    $product->addToCategories($catAll);     // After product is submitted insert all categories

    // Insert "feature name" and "feature value"
    if (is_array($features)) {
        foreach ($features as $feature) {
            $attributeName = $feature['name'];
            $attributeValue = $feature['value'];

            // 1. Check if 'feature name' exist already in database
            $FeatureNameId = Db::getInstance()->getValue('SELECT id_feature FROM ' . _DB_PREFIX_ . 'feature_lang WHERE name = "' . pSQL($attributeName) . '"');
            // If 'feature name' does not exist, insert new.
            if (empty($FeatureNameId)) {
                Db::getInstance()->execute('INSERT INTO `' . _DB_PREFIX_ . 'feature` (`id_feature`,`position`) VALUES (0, 0)');
                $FeatureNameId = Db::getInstance()->Insert_ID(); // Get id of "feature name" for insert in product
                Db::getInstance()->execute('INSERT INTO `' . _DB_PREFIX_ . 'feature_shop` (`id_feature`,`id_shop`) VALUES (' . $FeatureNameId . ', 1)');
                Db::getInstance()->execute('INSERT INTO `' . _DB_PREFIX_ . 'feature_lang` (`id_feature`,`id_lang`, `name`) VALUES (' . $FeatureNameId . ', ' . Context::getContext()->language->id . ', "' . pSQL($attributeName) . '")');
            }

            // 1. Check if 'feature value name' exist already in database
            $FeatureValueId = Db::getInstance()->getValue('SELECT id_feature_value FROM ' . _DB_PREFIX_ . 'feature_value WHERE id_feature_value IN (SELECT id_feature_value FROM `' . _DB_PREFIX_ . 'feature_value_lang` WHERE value = "' . pSQL($attributeValue) . '") AND id_feature = ' . $FeatureNameId);
            // If 'feature value name' does not exist, insert new.
            if (empty($FeatureValueId)) {
                Db::getInstance()->execute('INSERT INTO `' . _DB_PREFIX_ . 'feature_value` (`id_feature_value`,`id_feature`,`custom`) VALUES (0, ' . $FeatureNameId . ', 0)');
                $FeatureValueId = Db::getInstance()->Insert_ID();
                Db::getInstance()->execute('INSERT INTO `' . _DB_PREFIX_ . 'feature_value_lang` (`id_feature_value`,`id_lang`,`value`) VALUES (' . $FeatureValueId . ', ' . Context::getContext()->language->id . ', "' . pSQL($attributeValue) . '")');
            }
            Db::getInstance()->execute('INSERT INTO `' . _DB_PREFIX_ . 'feature_product` (`id_feature`, `id_product`, `id_feature_value`) VALUES (' . $FeatureNameId . ', ' . $product->id . ', ' . $FeatureValueId . ')');
        }
    }

    // add product image.
    $shops = Shop::getShops(true, null, true);
    $image = new Image();
    $image->id_product = $product->id;
    $image->position = Image::getHighestPosition($product->id) + 1;
    $image->cover = true;
    if (($image->validateFields(false, true)) === true && ($image->validateFieldsLang(false, true)) === true && $image->add()) {
        $image->associateTo($shops);
        if (!uploadImage($product->id, $image->id, $imgUrl)) {
            $image->delete();
        }
    }
    //echo 'Product added successfully (ID: ' . $product->id . ')';
    //echo 'PRODUCTOS MIGRADOS A PRESTASHOP CON EXITO';
    //echo 'Product added successfully (ID: ' . $product->id . ')';
    echo "Producto " . $product->id . " se migró" . nl2br("\n");
    
}

echo nl2br("\n\nLos datos se migraron correctamente a Prestashop");

    
function uploadImage($id_entity, $id_image = null, $imgUrl) {
    $tmpfile = tempnam(_PS_TMP_IMG_DIR_, 'ps_import');
    $watermark_types = explode(',', Configuration::get('WATERMARK_TYPES'));
    $image_obj = new Image((int)$id_image);
    $path = $image_obj->getPathForCreation();
    $imgUrl = str_replace(' ', '%20', trim($imgUrl));
    // Evaluate the memory required to resize the image: if it's too big we can't resize it.
    if (!ImageManager::checkImageMemoryLimit($imgUrl)) {
        return false;
    }
    if (@copy($imgUrl, $tmpfile)) {
        ImageManager::resize($tmpfile, $path . '.jpg');
        $images_types = ImageType::getImagesTypes('products');
        foreach ($images_types as $image_type) {
            ImageManager::resize($tmpfile, $path . '-' . stripslashes($image_type['name']) . '.jpg', $image_type['width'], $image_type['height']);
            if (in_array($image_type['id_image_type'], $watermark_types)) {
            Hook::exec('actionWatermark', array('id_image' => $id_image, 'id_product' => $id_entity));
            }
        }
    } else {
        unlink($tmpfile);
        return false;
    }
    unlink($tmpfile);
    return true;
}
