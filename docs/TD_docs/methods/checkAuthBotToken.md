---
title: checkAuthBotToken
description: Check bot's authentication token to log in as a bot. Works only when authGetState returns authStateWaitPhoneNumber. Can be used instead of setAuthPhoneNumber and checkAuthCode to log in. Returns authStateOk on success
---
## Method: checkAuthBotToken  
[Back to methods index](index.md)


YOU CANNOT USE THIS METHOD IN MADELINEPROTO


Check bot's authentication token to log in as a bot. Works only when authGetState returns authStateWaitPhoneNumber. Can be used instead of setAuthPhoneNumber and checkAuthCode to log in. Returns authStateOk on success

### Params:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|token|[string](../types/string.md) | Yes|Bot token|


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

$AuthState = $MadelineProto->checkAuthBotToken(['token' => 'string', ]);
```

Or, if you're using the [PWRTelegram HTTP API](https://pwrtelegram.xyz):

### As a bot:

POST/GET to `https://api.pwrtelegram.xyz/botTOKEN/madeline`

Parameters:

* method - checkAuthBotToken
* params - `{"token": "string", }`



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/checkAuthBotToken`

Parameters:

token - Json encoded string




Or, if you're into Lua:

```
AuthState = checkAuthBotToken({token='string', })
```

