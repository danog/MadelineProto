---
title: auth.importAuthorization
description: auth.importAuthorization parameters, return type and example
---
## Method: auth.importAuthorization  
[Back to methods index](index.md)


### Parameters:

| Name     |    Type       | Required |
|----------|---------------|----------|
|id|[int](../types/int.md) | Yes|
|bytes|[bytes](../types/bytes.md) | Yes|


### Return type: [auth\_Authorization](../types/auth_Authorization.md)

### Can bots use this method: **YES**


### Errors this method can return:

| Error    | Description   |
|----------|---------------|
|AUTH_BYTES_INVALID|The provided authorization is invalid|
|USER_ID_INVALID|The provided user ID is invalid|


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

$auth_Authorization = $MadelineProto->auth->importAuthorization(['id' => int, 'bytes' => 'bytes', ]);
```

Or, if you're using the [PWRTelegram HTTP API](https://pwrtelegram.xyz):

### As a bot:

POST/GET to `https://api.pwrtelegram.xyz/botTOKEN/madeline`

Parameters:

* method - auth.importAuthorization
* params - `{"id": int, "bytes": "bytes", }`



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/auth.importAuthorization`

Parameters:

id - Json encoded int

bytes - Json encoded bytes




Or, if you're into Lua:

```
auth_Authorization = auth.importAuthorization({id=int, bytes='bytes', })
```

