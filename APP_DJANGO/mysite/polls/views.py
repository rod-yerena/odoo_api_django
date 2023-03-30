#from django.shortcuts import render
from django.http import HttpResponse
from django.shortcuts import render, get_object_or_404, redirect
from django.contrib import messages
from .models import Producto
from django.views.decorators.http import require_POST
import json



def index(request):
    return verTodos(request)


#VER TODOS LOS PRODUCTOS
def verTodos(request):
    productos = Producto.objects.all()
    
    return render(request, 'product/allproducts.html', {'productos': productos})

#VER PRODUCTO DETALLADO:
def verProducto(request, nombreProducto='Mario Kart'):
    producto = Producto.objects.get(name_product=nombreProducto)
    
    return render(request, 'product/product.html', {'producto': producto})
    #return HttpResponse(producto_json, content_type='application/json')

def product_view(request, id):
    producto = Producto.objects.get(id=id)
    return render(request, 'product/product.html', {'producto': producto})


# Vista para agregar nuevo producto:

def addProducto(request):
    if request.method == 'POST':
        name_product = request.POST.get('name_product')
        price_product = request.POST.get('price_product')
        descrip_product = request.POST.get('descrip_product')
        img_product = request.POST.get('img_product')
        stock_product = request.POST.get('stock_product') == 'on'
        
        producto = Producto(name_product=name_product, price_product=price_product, descrip_product=descrip_product, img_product=img_product, stock_product=stock_product)
        producto.save()
        
        return redirect('/polls', id=producto.id)
    
    return render(request, 'product/addproduct.html')


# Vista para actualizar un producto
def updateProducto(request, id):
    producto = get_object_or_404(Producto, id=id)
    if request.method == 'POST':
        producto.name_product = request.POST.get('name_product')
        producto.price_product = request.POST.get('price_product')
        producto.descrip_product = request.POST.get('descrip_product')
        producto.img_product = request.POST.get('img_product')
        producto.stock_product = request.POST.get('stock_product') == 'on'
        producto.save()
        return redirect('/polls', id=producto.id)
    context = {'producto': producto}
    return render(request, 'product/updateproduct.html', context)


def deleteProducto(request, id):
    product_id = request.POST.get('id')
    product = Producto.objects.get(id=id)
    product.delete()
    return redirect('/polls')



# Create your views here.
