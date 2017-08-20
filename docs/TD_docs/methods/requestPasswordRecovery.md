---
title: requestPasswordRecovery
description: Requests to send password recovery code to email
---
## Method: requestPasswordRecovery  
[Back to methods index](index.md)


YOU CANNOT USE THIS METHOD IN MADELINEPROTO


Requests to send password recovery code to email

### Params:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|


### Return type: [PasswordRecoveryInfo](../types/PasswordRecoveryInfo.md)

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

$PasswordRecoveryInfo = $MadelineProto->requestPasswordRecovery();
```

Or, if you're using the [PWRTelegram HTTP API](https://pwrtelegram.xyz):

### As a bot:

POST/GET to `https://api.pwrtelegram.xyz/botTOKEN/madeline`

Parameters:

* method - requestPasswordRecovery
* params - `{}`



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/requestPasswordRecovery`

Parameters:




Or, if you're into Lua:

```
PasswordRecoveryInfo = requestPasswordRecovery({})
```

