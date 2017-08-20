---
title: checkChangePhoneNumberCode
description: Checks authentication code sent to change user's phone number. Returns authStateOk on success
---
## Method: checkChangePhoneNumberCode  
[Back to methods index](index.md)


YOU CANNOT USE THIS METHOD IN MADELINEPROTO


Checks authentication code sent to change user's phone number. Returns authStateOk on success

### Params:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|code|[string](../types/string.md) | Yes|Verification code from SMS, voice call or flash call|


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

$AuthState = $MadelineProto->checkChangePhoneNumberCode(['code' => 'string', ]);
```

Or, if you're using the [PWRTelegram HTTP API](https://pwrtelegram.xyz):

### As a bot:

POST/GET to `https://api.pwrtelegram.xyz/botTOKEN/madeline`

Parameters:

* method - checkChangePhoneNumberCode
* params - `{"code": "string", }`



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/checkChangePhoneNumberCode`

Parameters:

code - Json encoded string




Or, if you're into Lua:

```
AuthState = checkChangePhoneNumberCode({code='string', })
```

