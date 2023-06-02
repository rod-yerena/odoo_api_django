from django.db import models

class Question(models.Model):
    question_text = models.CharField(max_length=200)
    pub_date = models.DateTimeField('date published')


class Choice(models.Model):
    question = models.ForeignKey(Question, on_delete=models.CASCADE)
    choice_text = models.CharField(max_length=200)
    votes = models.IntegerField(default=0)


#CREACIÓN DE LA TABLA "PRODUCTO":
class Producto(models.Model):
    name_product = models.CharField(max_length=100)
    price_product = models.DecimalField(max_digits=5, decimal_places=2)
    descrip_product = models.TextField()
    img_product = models.CharField(max_length=200)
    stock_product = models.BooleanField(null=True)
