---
title: inputPaymentCredentialsAndroidPay
description: inputPaymentCredentialsAndroidPay attributes, type and example
---
## Constructor: inputPaymentCredentialsAndroidPay  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|---------------|----------|
|payment\_token|[DataJSON](../types/DataJSON.md) | Yes|
|google\_transaction\_id|[string](../types/string.md) | Yes|



### Type: [InputPaymentCredentials](../types/InputPaymentCredentials.md)


### Example:

```
$inputPaymentCredentialsAndroidPay = ['_' => 'inputPaymentCredentialsAndroidPay', 'payment_token' => DataJSON, 'google_transaction_id' => 'string'];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "inputPaymentCredentialsAndroidPay", "payment_token": DataJSON, "google_transaction_id": "string"}
```


Or, if you're into Lua:  


```
inputPaymentCredentialsAndroidPay={_='inputPaymentCredentialsAndroidPay', payment_token=DataJSON, google_transaction_id='string'}

```


