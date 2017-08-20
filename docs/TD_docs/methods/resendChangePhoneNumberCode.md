---
title: resendChangePhoneNumberCode
description: Resends authentication code sent to change user's phone number. Wotks only if in previously received authStateWaitCode next_code_type was not null. Returns authStateWaitCode on success
---
## Method: resendChangePhoneNumberCode  
[Back to methods index](index.md)


YOU CANNOT USE THIS METHOD IN MADELINEPROTO


Resends authentication code sent to change user's phone number. Wotks only if in previously received authStateWaitCode next_code_type was not null. Returns authStateWaitCode on success

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

$AuthState = $MadelineProto->resendChangePhoneNumberCode();
```

Or, if you're using the [PWRTelegram HTTP API](https://pwrtelegram.xyz):

### As a bot:

POST/GET to `https://api.pwrtelegram.xyz/botTOKEN/madeline`

Parameters:

* method - resendChangePhoneNumberCode
* params - `{}`



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/resendChangePhoneNumberCode`

Parameters:




Or, if you're into Lua:

```
AuthState = resendChangePhoneNumberCode({})
```

