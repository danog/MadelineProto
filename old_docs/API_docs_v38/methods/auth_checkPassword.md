---
title: auth.checkPassword
description: auth.checkPassword parameters, return type and example
---
## Method: auth.checkPassword  
[Back to methods index](index.md)


### Parameters:

| Name     |    Type       | Required |
|----------|---------------|----------|
|password\_hash|[bytes](../types/bytes.md) | Yes|


### Return type: [auth\_Authorization](../types/auth_Authorization.md)

### Can bots use this method: **NO**


### Errors this method can return:

| Error    | Description   |
|----------|---------------|
|PASSWORD_HASH_INVALID|The provided password hash is invalid|


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

$auth_Authorization = $MadelineProto->auth->checkPassword(['password_hash' => 'bytes', ]);
```

Or, if you're using the [PWRTelegram HTTP API](https://pwrtelegram.xyz):



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/auth.checkPassword`

Parameters:

password_hash - Json encoded bytes




Or, if you're into Lua:

```
auth_Authorization = auth.checkPassword({password_hash='bytes', })
```

