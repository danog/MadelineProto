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
|msg\_id|[CLICK ME int](../types/int.md) | Yes|Message ID|


### Return type: [payments\_PaymentForm](../types/payments_PaymentForm.md)

### Can bots use this method: **NO**


### Errors this method can return:

| Error    | Description   |
|----------|---------------|
|MESSAGE_ID_INVALID|The provided message id is invalid|


### MadelineProto Example:


```
if (!file_exists('madeline.php')) {
    copy('https://phar.madelineproto.xyz/madeline.php', 'madeline.php');
}
include 'madeline.php';

// !!! This API id/API hash combination will not work !!!
// !!! You must get your own @ my.telegram.org !!!
$api_id = 0;
$api_hash = '';

$MadelineProto = new \danog\MadelineProto\API('session.madeline', ['app_info' => ['api_id' => $api_id, 'api_hash' => $api_hash]]);
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

