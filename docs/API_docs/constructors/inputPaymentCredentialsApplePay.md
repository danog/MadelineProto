---
title: inputPaymentCredentialsApplePay
description: inputPaymentCredentialsApplePay attributes, type and example
---
## Constructor: inputPaymentCredentialsApplePay  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|---------------|----------|
|payment\_data|[DataJSON](../types/DataJSON.md) | Yes|



### Type: [InputPaymentCredentials](../types/InputPaymentCredentials.md)


### Example:

```
$inputPaymentCredentialsApplePay = ['_' => 'inputPaymentCredentialsApplePay', 'payment_data' => DataJSON];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "inputPaymentCredentialsApplePay", "payment_data": DataJSON}
```


Or, if you're into Lua:  


```
inputPaymentCredentialsApplePay={_='inputPaymentCredentialsApplePay', payment_data=DataJSON}

```


