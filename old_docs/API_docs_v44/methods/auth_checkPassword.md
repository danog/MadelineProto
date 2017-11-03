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

