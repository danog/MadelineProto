---
title: auth.recoverPassword
description: auth.recoverPassword parameters, return type and example
---
## Method: auth.recoverPassword  
[Back to methods index](index.md)


### Parameters:

| Name     |    Type       | Required |
|----------|---------------|----------|
|code|[string](../types/string.md) | Yes|


### Return type: [auth\_Authorization](../types/auth_Authorization.md)

### Can bots use this method: **NO**


### Errors this method can return:

| Error    | Description   |
|----------|---------------|
|CODE_EMPTY|The provided code is empty|


### Example:


```
$MadelineProto = new \danog\MadelineProto\API();
$MadelineProto->session = 'mySession.madeline';
if (isset($number)) { // Login as a user
    $MadelineProto->phone_login($number);
    $code = readline('Enter the code you received: '); // Or do this in two separate steps in an HTTP API
    $MadelineProto->complete_phone_login($code);
}

$auth_Authorization = $MadelineProto->auth->recoverPassword(['code' => 'string', ]);
```

Or, if you're using the [PWRTelegram HTTP API](https://pwrtelegram.xyz):



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/auth.recoverPassword`

Parameters:

code - Json encoded string




Or, if you're into Lua:

```
auth_Authorization = auth.recoverPassword({code='string', })
```

