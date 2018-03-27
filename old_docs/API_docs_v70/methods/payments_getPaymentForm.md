---
title: payments.getPaymentForm
description: Get payment form
---
## Method: payments.getPaymentForm  
[Back to methods index](index.md)


Get payment form

### Parameters:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|msg\_id|[int](../types/int.md) | Yes|Message ID|


### Return type: [payments\_PaymentForm](../types/payments_PaymentForm.md)

### Can bots use this method: **NO**


### MadelineProto Example:


```
if (!file_exists('madeline.php')) {
    copy('https://phar.madelineproto.xyz/madeline.php', 'madeline.php');
}
include 'madeline.php';

$MadelineProto = new \danog\MadelineProto\API('session.madeline');
$MadelineProto->start();

$payments_PaymentForm = $MadelineProto->payments->getPaymentForm(['msg_id' => int, ]);
```

### [PWRTelegram HTTP API](https://pwrtelegram.xyz) example (NOT FOR MadelineProto):



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/payments.getPaymentForm`

Parameters:

msg_id - Json encoded int




Or, if you're into Lua:

```
payments_PaymentForm = payments.getPaymentForm({msg_id=int, })
```

### Errors this method can return:

| Error    | Description   |
|----------|---------------|
|MESSAGE_ID_INVALID|The provided message id is invalid|


