---
title: recoverPassword
description: Recovers password with recovery code sent to email
---
## Method: recoverPassword  
[Back to methods index](index.md)


YOU CANNOT USE THIS METHOD IN MADELINEPROTO


Recovers password with recovery code sent to email

### Params:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|recovery\_code|[string](../types/string.md) | Yes|Recovery code to check|


### Return type: [PasswordState](../types/PasswordState.md)

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

$PasswordState = $MadelineProto->recoverPassword(['recovery_code' => 'string', ]);
```

Or, if you're using the [PWRTelegram HTTP API](https://pwrtelegram.xyz):

### As a bot:

POST/GET to `https://api.pwrtelegram.xyz/botTOKEN/madeline`

Parameters:

* method - recoverPassword
* params - `{"recovery_code": "string", }`



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/recoverPassword`

Parameters:

recovery_code - Json encoded string




Or, if you're into Lua:

```
PasswordState = recoverPassword({recovery_code='string', })
```

