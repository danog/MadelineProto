---
title: checkAuthCode
description: Checks authentication code. Works only when authGetState returns authStateWaitCode. Returns authStateWaitPassword or authStateOk on success
---
## Method: checkAuthCode  
[Back to methods index](index.md)


YOU CANNOT USE THIS METHOD IN MADELINEPROTO


Checks authentication code. Works only when authGetState returns authStateWaitCode. Returns authStateWaitPassword or authStateOk on success

### Params:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|code|[string](../types/string.md) | Yes|Verification code from SMS, Telegram message, voice call or flash call|
|first\_name|[string](../types/string.md) | Yes|User first name, if user is yet not registered, 1-255 characters|
|last\_name|[string](../types/string.md) | Yes|Optional user last name, if user is yet not registered, 0-255 characters|


### Return type: [AuthState](../types/AuthState.md)

### Example:


```
$MadelineProto = new \danog\MadelineProto\API();
if (isset($token)) { // Login as a bot
    $MadelineProto->bot_login($token);
}
if (isset($number)) { // Login as a user
    $sentCode = $MadelineProto->phone_login($number);
    echo 'Enter the code you received: ';
    $code = '';
    for ($x = 0; $x < $sentCode['type']['length']; $x++) {
        $code .= fgetc(STDIN);
    }
    $MadelineProto->complete_phone_login($code);
}

$AuthState = $MadelineProto->checkAuthCode(['code' => 'string', 'first_name' => 'string', 'last_name' => 'string', ]);
```

Or, if you're using the [PWRTelegram HTTP API](https://pwrtelegram.xyz):

### As a bot:

POST/GET to `https://api.pwrtelegram.xyz/botTOKEN/madeline`

Parameters:

* method - checkAuthCode
* params - `{"code": "string", "first_name": "string", "last_name": "string", }`



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/checkAuthCode`

Parameters:

code - Json encoded string

first_name - Json encoded string

last_name - Json encoded string




Or, if you're into Lua:

```
AuthState = checkAuthCode({code='string', first_name='string', last_name='string', })
```

