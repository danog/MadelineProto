---
title: payments.paymentForm
description: payments_paymentForm attributes, type and example
---
## Constructor: payments.paymentForm  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|---------------|----------|
|can\_save\_credentials|[Bool](../types/Bool.md) | Optional|
|password\_missing|[Bool](../types/Bool.md) | Optional|
|bot\_id|[int](../types/int.md) | Yes|
|invoice|[Invoice](../types/Invoice.md) | Yes|
|provider\_id|[int](../types/int.md) | Yes|
|url|[string](../types/string.md) | Yes|
|native\_provider|[string](../types/string.md) | Optional|
|native\_params|[DataJSON](../types/DataJSON.md) | Optional|
|saved\_info|[PaymentRequestedInfo](../types/PaymentRequestedInfo.md) | Optional|
|saved\_credentials|[PaymentSavedCredentials](../types/PaymentSavedCredentials.md) | Optional|
|users|Array of [User](../types/User.md) | Yes|



### Type: [payments\_PaymentForm](../types/payments_PaymentForm.md)


### Example:

```
$payments_paymentForm = ['_' => 'payments.paymentForm', 'can_save_credentials' => Bool, 'password_missing' => Bool, 'bot_id' => int, 'invoice' => Invoice, 'provider_id' => int, 'url' => 'string', 'native_provider' => 'string', 'native_params' => DataJSON, 'saved_info' => PaymentRequestedInfo, 'saved_credentials' => PaymentSavedCredentials, 'users' => [User]];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "payments.paymentForm", "can_save_credentials": Bool, "password_missing": Bool, "bot_id": int, "invoice": Invoice, "provider_id": int, "url": "string", "native_provider": "string", "native_params": DataJSON, "saved_info": PaymentRequestedInfo, "saved_credentials": PaymentSavedCredentials, "users": [User]}
```


Or, if you're into Lua:  


```
payments_paymentForm={_='payments.paymentForm', can_save_credentials=Bool, password_missing=Bool, bot_id=int, invoice=Invoice, provider_id=int, url='string', native_provider='string', native_params=DataJSON, saved_info=PaymentRequestedInfo, saved_credentials=PaymentSavedCredentials, users={User}}

```


