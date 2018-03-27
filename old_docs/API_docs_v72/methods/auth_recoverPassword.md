---
title: auth.recoverPassword
description: Use the code that was emailed to you after running $MadelineProto->auth->requestPasswordRecovery to login to your account
---
## Method: auth.recoverPassword  
[Back to methods index](index.md)


Use the code that was emailed to you after running $MadelineProto->auth->requestPasswordRecovery to login to your account

### Parameters:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|code|[string](../types/string.md) | Yes|The code that was emailed to you|


### Return type: [auth\_Authorization](../types/auth_Authorization.md)

### Can bots use this method: **NO**


### MadelineProto Example:


```
if (!file_exists('madeline.php')) {
    copy('https://phar.madelineproto.xyz/madeline.php', 'madeline.php');
}
include 'madeline.php';

$MadelineProto = new \danog\MadelineProto\API('session.madeline');
$MadelineProto->start();

$auth_Authorization = $MadelineProto->auth->recoverPassword(['code' => 'string', ]);
```

### [PWRTelegram HTTP API](https://pwrtelegram.xyz) example (NOT FOR MadelineProto):



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/auth.recoverPassword`

Parameters:

code - Json encoded string




Or, if you're into Lua:

```
auth_Authorization = auth.recoverPassword({code='string', })
```

### Errors this method can return:

| Error    | Description   |
|----------|---------------|
|CODE_EMPTY|The provided code is empty|


