---
title: payments.getPaymentReceipt
description: payments.getPaymentReceipt parameters, return type and example
---
## Method: payments.getPaymentReceipt  
[Back to methods index](index.md)


### Parameters:

| Name     |    Type       | Required |
|----------|---------------|----------|
|msg\_id|[int](../types/int.md) | Yes|


### Return type: [payments\_PaymentReceipt](../types/payments_PaymentReceipt.md)

### Can bots use this method: **NO**


### Errors this method can return:

| Error    | Description   |
|----------|---------------|
|MESSAGE_ID_INVALID|The provided message id is invalid|


### Example:


```
$MadelineProto = new \danog\MadelineProto\API();
$MadelineProto->session = 'mySession.madeline';
if (isset($number)) { // Login as a user
    $MadelineProto->phone_login($number);
    $code = readline('Enter the code you received: '); // Or do this in two separate steps in an HTTP API
    $MadelineProto->complete_phone_login($code);
}

$payments_PaymentReceipt = $MadelineProto->payments->getPaymentReceipt(['msg_id' => int, ]);
```

Or, if you're using the [PWRTelegram HTTP API](https://pwrtelegram.xyz):



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/payments.getPaymentReceipt`

Parameters:

msg_id - Json encoded int




Or, if you're into Lua:

```
payments_PaymentReceipt = payments.getPaymentReceipt({msg_id=int, })
```

