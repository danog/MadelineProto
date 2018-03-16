---
title: auth.bindTempAuthKey
description: auth.bindTempAuthKey parameters, return type and example
---
## Method: auth.bindTempAuthKey  
[Back to methods index](index.md)


### Parameters:

| Name     |    Type       | Required |
|----------|---------------|----------|
|perm\_auth\_key\_id|[long](../types/long.md) | Yes|
|nonce|[long](../types/long.md) | Yes|
|expires\_at|[int](../types/int.md) | Yes|
|encrypted\_message|[bytes](../types/bytes.md) | Yes|


### Return type: [Bool](../types/Bool.md)

### Can bots use this method: **YES**


### Errors this method can return:

| Error    | Description   |
|----------|---------------|
|ENCRYPTED_MESSAGE_INVALID|Encrypted message invalid|
|INPUT_REQUEST_TOO_LONG|The request is too big|
|TEMP_AUTH_KEY_EMPTY|No temporary auth key provided|
|Timeout|A timeout occurred while fetching data from the bot|


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

$Bool = $MadelineProto->auth->bindTempAuthKey(['perm_auth_key_id' => long, 'nonce' => long, 'expires_at' => int, 'encrypted_message' => 'bytes', ]);
```

Or, if you're using the [PWRTelegram HTTP API](https://pwrtelegram.xyz):

### As a bot:

POST/GET to `https://api.pwrtelegram.xyz/botTOKEN/madeline`

Parameters:

* method - auth.bindTempAuthKey
* params - `{"perm_auth_key_id": long, "nonce": long, "expires_at": int, "encrypted_message": "bytes", }`



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/auth.bindTempAuthKey`

Parameters:

perm_auth_key_id - Json encoded long

nonce - Json encoded long

expires_at - Json encoded int

encrypted_message - Json encoded bytes




Or, if you're into Lua:

```
Bool = auth.bindTempAuthKey({perm_auth_key_id=long, nonce=long, expires_at=int, encrypted_message='bytes', })
```

