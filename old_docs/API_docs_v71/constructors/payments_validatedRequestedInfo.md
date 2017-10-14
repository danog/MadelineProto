---
title: payments.validatedRequestedInfo
description: payments_validatedRequestedInfo attributes, type and example
---
## Constructor: payments.validatedRequestedInfo  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|---------------|----------|
|id|[string](../types/string.md) | Optional|
|shipping\_options|Array of [ShippingOption](../types/ShippingOption.md) | Optional|



### Type: [payments\_ValidatedRequestedInfo](../types/payments_ValidatedRequestedInfo.md)


### Example:

```
$payments_validatedRequestedInfo = ['_' => 'payments.validatedRequestedInfo', 'id' => 'string', 'shipping_options' => [ShippingOption]];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "payments.validatedRequestedInfo", "id": "string", "shipping_options": [ShippingOption]}
```


Or, if you're into Lua:  


```
payments_validatedRequestedInfo={_='payments.validatedRequestedInfo', id='string', shipping_options={ShippingOption}}

```


