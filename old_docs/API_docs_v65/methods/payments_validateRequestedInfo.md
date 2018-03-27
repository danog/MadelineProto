---
title: payments.validateRequestedInfo
description: Validate requested payment info
---
## Method: payments.validateRequestedInfo  
[Back to methods index](index.md)


Validate requested payment info

### Parameters:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|save|[Bool](../types/Bool.md) | Optional|Save payment info?|
|msg\_id|[int](../types/int.md) | Yes|The message ID|
|info|[PaymentRequestedInfo](../types/PaymentRequestedInfo.md) | Yes|The requested payment info|


### Return type: [payments\_ValidatedRequestedInfo](../types/payments_ValidatedRequestedInfo.md)

### Can bots use this method: **NO**


### MadelineProto Example:


```
if (!file_exists('madeline.php')) {
    copy('https://phar.madelineproto.xyz/madeline.php', 'madeline.php');
}
include 'madeline.php';

$MadelineProto = new \danog\MadelineProto\API('session.madeline');
$MadelineProto->start();

$payments_ValidatedRequestedInfo = $MadelineProto->payments->validateRequestedInfo(['save' => Bool, 'msg_id' => int, 'info' => PaymentRequestedInfo, ]);
```

### [PWRTelegram HTTP API](https://pwrtelegram.xyz) example (NOT FOR MadelineProto):



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/payments.validateRequestedInfo`

Parameters:

save - Json encoded Bool

msg_id - Json encoded int

info - Json encoded PaymentRequestedInfo




Or, if you're into Lua:

```
payments_ValidatedRequestedInfo = payments.validateRequestedInfo({save=Bool, msg_id=int, info=PaymentRequestedInfo, })
```

### Errors this method can return:

| Error    | Description   |
|----------|---------------|
|MESSAGE_ID_INVALID|The provided message id is invalid|


