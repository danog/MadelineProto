---
title: shippingOption
description: shippingOption attributes, type and example
---
## Constructor: shippingOption  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|---------------|----------|
|id|[string](../types/string.md) | Yes|
|title|[string](../types/string.md) | Yes|
|prices|Array of [LabeledPrice](../types/LabeledPrice.md) | Yes|



### Type: [ShippingOption](../types/ShippingOption.md)


### Example:

```
$shippingOption = ['_' => 'shippingOption', 'id' => 'string', 'title' => 'string', 'prices' => [LabeledPrice]];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "shippingOption", "id": "string", "title": "string", "prices": [LabeledPrice]}
```


Or, if you're into Lua:  


```
shippingOption={_='shippingOption', id='string', title='string', prices={LabeledPrice}}

```


