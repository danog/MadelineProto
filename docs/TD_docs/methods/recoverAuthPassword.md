---
title: recoverAuthPassword
description: Recovers password with recovery code sent to email. Works only when authGetState returns authStateWaitPassword. Returns authStateOk on success
---
## Method: recoverAuthPassword  
[Back to methods index](index.md)


YOU CANNOT USE THIS METHOD IN MADELINEPROTO


Recovers password with recovery code sent to email. Works only when authGetState returns authStateWaitPassword. Returns authStateOk on success

### Params:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|recovery\_code|[string](../types/string.md) | Yes|Recovery code to check|


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

$AuthState = $MadelineProto->recoverAuthPassword(['recovery_code' => 'string', ]);
```

Or, if you're using the [PWRTelegram HTTP API](https://pwrtelegram.xyz):

### As a bot:

POST/GET to `https://api.pwrtelegram.xyz/botTOKEN/madeline`

Parameters:

* method - recoverAuthPassword
* params - `{"recovery_code": "string", }`



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/recoverAuthPassword`

Parameters:

recovery_code - Json encoded string




Or, if you're into Lua:

```
AuthState = recoverAuthPassword({recovery_code='string', })
```

