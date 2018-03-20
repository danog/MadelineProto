---
title: auth.requestPasswordRecovery
description: Send an email to recover the 2FA password
---
## Method: auth.requestPasswordRecovery  
[Back to methods index](index.md)


Send an email to recover the 2FA password



### Return type: [auth\_PasswordRecovery](../types/auth_PasswordRecovery.md)

### Can bots use this method: **NO**


### Errors this method can return:

| Error    | Description   |
|----------|---------------|
|PASSWORD_EMPTY|The provided password is empty|


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

$auth_PasswordRecovery = $MadelineProto->auth->requestPasswordRecovery();
```

### [PWRTelegram HTTP API](https://pwrtelegram.xyz) example (NOT FOR MadelineProto):



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/auth.requestPasswordRecovery`

Parameters:




Or, if you're into Lua:

```
auth_PasswordRecovery = auth.requestPasswordRecovery({})
```

