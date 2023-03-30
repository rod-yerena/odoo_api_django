from django.urls import path
from . import views #importa todas las vistas

urlpatterns = [
    path('', views.index, name='index'),
    path('product-view/<int:id>/', views.updateProducto, name='product_view'),
    path('product/add/', views.addProducto, name='add_product'),
    path('eliminar/<int:id>/', views.deleteProducto, name='eliminar_producto')
]


