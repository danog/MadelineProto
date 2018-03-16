---
title: payments.validateRequestedInfo
description: payments.validateRequestedInfo parameters, return type and example
---
## Method: payments.validateRequestedInfo  
[Back to methods index](index.md)


### Parameters:

| Name     |    Type       | Required |
|----------|---------------|----------|
|save|[Bool](../types/Bool.md) | Optional|
|msg\_id|[int](../types/int.md) | Yes|
|info|[PaymentRequestedInfo](../types/PaymentRequestedInfo.md) | Yes|


### Return type: [payments\_ValidatedRequestedInfo](../types/payments_ValidatedRequestedInfo.md)

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

$payments_ValidatedRequestedInfo = $MadelineProto->payments->validateRequestedInfo(['save' => Bool, 'msg_id' => int, 'info' => PaymentRequestedInfo, ]);
```

Or, if you're using the [PWRTelegram HTTP API](https://pwrtelegram.xyz):



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

