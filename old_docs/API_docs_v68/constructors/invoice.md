---
title: invoice
description: invoice attributes, type and example
---
## Constructor: invoice  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|---------------|----------|
|test|[Bool](../types/Bool.md) | Optional|
|name\_requested|[Bool](../types/Bool.md) | Optional|
|phone\_requested|[Bool](../types/Bool.md) | Optional|
|email\_requested|[Bool](../types/Bool.md) | Optional|
|shipping\_address\_requested|[Bool](../types/Bool.md) | Optional|
|flexible|[Bool](../types/Bool.md) | Optional|
|currency|[string](../types/string.md) | Yes|
|prices|Array of [LabeledPrice](../types/LabeledPrice.md) | Yes|



### Type: [Invoice](../types/Invoice.md)


### Example:

```
$invoice = ['_' => 'invoice', 'test' => Bool, 'name_requested' => Bool, 'phone_requested' => Bool, 'email_requested' => Bool, 'shipping_address_requested' => Bool, 'flexible' => Bool, 'currency' => 'string', 'prices' => [LabeledPrice]];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "invoice", "test": Bool, "name_requested": Bool, "phone_requested": Bool, "email_requested": Bool, "shipping_address_requested": Bool, "flexible": Bool, "currency": "string", "prices": [LabeledPrice]}
```


Or, if you're into Lua:  


```
invoice={_='invoice', test=Bool, name_requested=Bool, phone_requested=Bool, email_requested=Bool, shipping_address_requested=Bool, flexible=Bool, currency='string', prices={LabeledPrice}}

```


