---
title: resendAuthCode
description: Resends authentication code to the user. Works only when authGetState returns authStateWaitCode and next_code_type of result is not null. Returns authStateWaitCode on success
---
## Method: resendAuthCode  
[Back to methods index](index.md)


YOU CANNOT USE THIS METHOD IN MADELINEPROTO


Resends authentication code to the user. Works only when authGetState returns authStateWaitCode and next_code_type of result is not null. Returns authStateWaitCode on success

### Params:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|


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

$AuthState = $MadelineProto->resendAuthCode();
```

Or, if you're using the [PWRTelegram HTTP API](https://pwrtelegram.xyz):

### As a bot:

POST/GET to `https://api.pwrtelegram.xyz/botTOKEN/madeline`

Parameters:

* method - resendAuthCode
* params - `{}`



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/resendAuthCode`

Parameters:




Or, if you're into Lua:

```
AuthState = resendAuthCode({})
```

