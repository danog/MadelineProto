---
title: invoice
description: Goods invoice
---
## Constructor: invoice  
[Back to constructors index](index.md)



Goods invoice

### Attributes:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|currency|[string](../types/string.md) | Yes|ISO 4217 currency code|
|prices|Array of [labeledPrice](../constructors/labeledPrice.md) | Yes|List of objects used to calculate total price|
|is\_test|[Bool](../types/Bool.md) | Yes|True, if payment is test|
|need\_name|[Bool](../types/Bool.md) | Yes|True, if user's name is needed for payment|
|need\_phone\_number|[Bool](../types/Bool.md) | Yes|True, if user's phone number is needed for payment|
|need\_email|[Bool](../types/Bool.md) | Yes|True, if user's email is needed for payment|
|need\_shipping\_address|[Bool](../types/Bool.md) | Yes|True, if user's shipping address is needed for payment|
|is\_flexible|[Bool](../types/Bool.md) | Yes|True, if total price depends on shipping method|



### Type: [Invoice](../types/Invoice.md)


