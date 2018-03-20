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

