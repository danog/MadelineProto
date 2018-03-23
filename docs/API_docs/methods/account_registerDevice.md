---
title: account.registerDevice
description: Register device for push notifications
---
## Method: account.registerDevice  
[Back to methods index](index.md)


Register device for push notifications

### Parameters:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|token\_type|[CLICK ME int](../types/int.md) | Yes|Device token type. Possible values: 1 - APNS, 2 - GCM, 3 - MPNS, 4 - Simple Push, 5 - Ubuntu Phone, 6 - Blackberry, and other, see source code of official apps for more info|
|token|[CLICK ME string](../types/string.md) | Yes|Device token type. Possible values: 1 - APNS, 2 - GCM, 3 - MPNS, 4 - Simple Push, 5 - Ubuntu Phone,6 - Blackberry, and other, see source code of official apps for more info|
|app\_sandbox|[CLICK ME Bool](../types/Bool.md) | Yes|Should the app run in a sandbox?|
|other\_uids|Array of [CLICK ME int](../types/int.md) | Yes|Other UIDs|


### Return type: [Bool](../types/Bool.md)

### Can bots use this method: **NO**


### Errors this method can return:

| Error    | Description   |
|----------|---------------|
|TOKEN_INVALID|The provided token is invalid|


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

$Bool = $MadelineProto->account->registerDevice(['token_type' => int, 'token' => 'string', 'app_sandbox' => Bool, 'other_uids' => [int, int], ]);
```

### [PWRTelegram HTTP API](https://pwrtelegram.xyz) example (NOT FOR MadelineProto):



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/account.registerDevice`

Parameters:

token_type - Json encoded int

token - Json encoded string

app_sandbox - Json encoded Bool

other_uids - Json encoded  array of int




Or, if you're into Lua:

```
Bool = account.registerDevice({token_type=int, token='string', app_sandbox=Bool, other_uids={int}, })
```

