---
title: payments.getPaymentReceipt
description: Get payment receipt
---
## Method: payments.getPaymentReceipt  
[Back to methods index](index.md)


Get payment receipt

### Parameters:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|msg\_id|[int](../types/int.md) | Yes|The message ID|


### Return type: [payments\_PaymentReceipt](../types/payments_PaymentReceipt.md)

### Can bots use this method: **NO**


### MadelineProto Example:


```
if (!file_exists('madeline.php')) {
    copy('https://phar.madelineproto.xyz/madeline.php', 'madeline.php');
}
include 'madeline.php';

$MadelineProto = new \danog\MadelineProto\API('session.madeline');
$MadelineProto->start();

$payments_PaymentReceipt = $MadelineProto->payments->getPaymentReceipt(['msg_id' => int, ]);
```

### [PWRTelegram HTTP API](https://pwrtelegram.xyz) example (NOT FOR MadelineProto):



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/payments.getPaymentReceipt`

Parameters:

msg_id - Json encoded int




Or, if you're into Lua:

```
payments_PaymentReceipt = payments.getPaymentReceipt({msg_id=int, })
```

### Errors this method can return:

| Error    | Description   |
|----------|---------------|
|MESSAGE_ID_INVALID|The provided message id is invalid|


