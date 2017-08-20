---
title: paymentCharge
description: paymentCharge attributes, type and example
---
## Constructor: paymentCharge  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|---------------|----------|
|id|[string](../types/string.md) | Yes|
|provider\_charge\_id|[string](../types/string.md) | Yes|



### Type: [PaymentCharge](../types/PaymentCharge.md)


### Example:

```
$paymentCharge = ['_' => 'paymentCharge', 'id' => 'string', 'provider_charge_id' => 'string'];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "paymentCharge", "id": "string", "provider_charge_id": "string"}
```


Or, if you're into Lua:  


```
paymentCharge={_='paymentCharge', id='string', provider_charge_id='string'}

```


