---
title: account.unregisterDevice
description: Stop sending PUSH notifications to app
---
## Method: account.unregisterDevice  
[Back to methods index](index.md)


Stop sending PUSH notifications to app

### Parameters:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|token\_type|[int](../types/int.md) | Yes|Device token type. Possible values: 1 - APNS, 2 - GCM, 3 - MPNS, 4 - Simple Push, 5 - Ubuntu Phone,6 - Blackberry, and other, see source code of official apps for more info|
|token|[string](../types/string.md) | Yes|Device token type. Possible values: 1 - APNS, 2 - GCM, 3 - MPNS, 4 - Simple Push, 5 - Ubuntu Phone,6 - Blackberry, and other, see source code of official apps for more info|
|other\_uids|Array of [int](../types/int.md) | Yes|Other UUIDs|


### Return type: [Bool](../types/Bool.md)

### Can bots use this method: **NO**


### Errors this method can return:

| Error    | Description   |
|----------|---------------|
|TOKEN_INVALID|The provided token is invalid|


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

$Bool = $MadelineProto->account->unregisterDevice(['token_type' => int, 'token' => 'string', 'other_uids' => [int], ]);
```

Or, if you're using the [PWRTelegram HTTP API](https://pwrtelegram.xyz):



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/account.unregisterDevice`

Parameters:

token_type - Json encoded int

token - Json encoded string

other_uids - Json encoded  array of int




Or, if you're into Lua:

```
Bool = account.unregisterDevice({token_type=int, token='string', other_uids={int}, })
```

