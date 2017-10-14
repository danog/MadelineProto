---
title: payments.paymentReceipt
description: payments_paymentReceipt attributes, type and example
---
## Constructor: payments.paymentReceipt  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|---------------|----------|
|date|[int](../types/int.md) | Yes|
|bot\_id|[int](../types/int.md) | Yes|
|invoice|[Invoice](../types/Invoice.md) | Yes|
|provider\_id|[int](../types/int.md) | Yes|
|info|[PaymentRequestedInfo](../types/PaymentRequestedInfo.md) | Optional|
|shipping|[ShippingOption](../types/ShippingOption.md) | Optional|
|currency|[string](../types/string.md) | Yes|
|total\_amount|[long](../types/long.md) | Yes|
|credentials\_title|[string](../types/string.md) | Yes|
|users|Array of [User](../types/User.md) | Yes|



### Type: [payments\_PaymentReceipt](../types/payments_PaymentReceipt.md)


### Example:

```
$payments_paymentReceipt = ['_' => 'payments.paymentReceipt', 'date' => int, 'bot_id' => int, 'invoice' => Invoice, 'provider_id' => int, 'info' => PaymentRequestedInfo, 'shipping' => ShippingOption, 'currency' => 'string', 'total_amount' => long, 'credentials_title' => 'string', 'users' => [User]];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "payments.paymentReceipt", "date": int, "bot_id": int, "invoice": Invoice, "provider_id": int, "info": PaymentRequestedInfo, "shipping": ShippingOption, "currency": "string", "total_amount": long, "credentials_title": "string", "users": [User]}
```


Or, if you're into Lua:  


```
payments_paymentReceipt={_='payments.paymentReceipt', date=int, bot_id=int, invoice=Invoice, provider_id=int, info=PaymentRequestedInfo, shipping=ShippingOption, currency='string', total_amount=long, credentials_title='string', users={User}}

```


