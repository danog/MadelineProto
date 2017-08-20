---
title: payments.paymentVerficationNeeded
description: payments_paymentVerficationNeeded attributes, type and example
---
## Constructor: payments.paymentVerficationNeeded  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|---------------|----------|
|url|[string](../types/string.md) | Yes|



### Type: [payments\_PaymentResult](../types/payments_PaymentResult.md)


### Example:

```
$payments_paymentVerficationNeeded = ['_' => 'payments.paymentVerficationNeeded', 'url' => 'string'];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "payments.paymentVerficationNeeded", "url": "string"}
```


Or, if you're into Lua:  


```
payments_paymentVerficationNeeded={_='payments.paymentVerficationNeeded', url='string'}

```


