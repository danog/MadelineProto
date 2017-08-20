---
title: changePhoneNumber
description: Changes user's phone number and sends authentication code to the new user's phone number. Returns authStateWaitCode with information about sent code on success
---
## Method: changePhoneNumber  
[Back to methods index](index.md)


YOU CANNOT USE THIS METHOD IN MADELINEPROTO


Changes user's phone number and sends authentication code to the new user's phone number. Returns authStateWaitCode with information about sent code on success

### Params:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|phone\_number|[string](../types/string.md) | Yes|New user's phone number in any reasonable format|
|allow\_flash\_call|[Bool](../types/Bool.md) | Yes|Pass True, if code can be sent via flash call to the specified phone number|
|is\_current\_phone\_number|[Bool](../types/Bool.md) | Yes|Pass true, if the phone number is used on the current device. Ignored if allow_flash_call is False|


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

$AuthState = $MadelineProto->changePhoneNumber(['phone_number' => 'string', 'allow_flash_call' => Bool, 'is_current_phone_number' => Bool, ]);
```

Or, if you're using the [PWRTelegram HTTP API](https://pwrtelegram.xyz):

### As a bot:

POST/GET to `https://api.pwrtelegram.xyz/botTOKEN/madeline`

Parameters:

* method - changePhoneNumber
* params - `{"phone_number": "string", "allow_flash_call": Bool, "is_current_phone_number": Bool, }`



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/changePhoneNumber`

Parameters:

phone_number - Json encoded string

allow_flash_call - Json encoded Bool

is_current_phone_number - Json encoded Bool




Or, if you're into Lua:

```
AuthState = changePhoneNumber({phone_number='string', allow_flash_call=Bool, is_current_phone_number=Bool, })
```

