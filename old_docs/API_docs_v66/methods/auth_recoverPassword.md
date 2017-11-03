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
if (isset($number)) { // Login as a user
    $sentCode = $MadelineProto->phone_login($number);
    echo 'Enter the code you received: ';
    $code = '';
    for ($x = 0; $x < $sentCode['type']['length']; $x++) {
        $code .= fgetc(STDIN);
    }
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

