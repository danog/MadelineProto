---
title: auth.sendCode
description: auth.sendCode parameters, return type and example
---
## Method: auth.sendCode  
[Back to methods index](index.md)


### Parameters:

| Name     |    Type       | Required |
|----------|:-------------:|---------:|
|allow\_flashcall|[Bool](../types/Bool.md) | Optional|
|phone\_number|[string](../types/string.md) | Yes|
|current\_number|[Bool](../types/Bool.md) | Optional|
|api\_id|[int](../types/int.md) | Yes|
|api\_hash|[string](../types/string.md) | Yes|


### Return type: [auth\_SentCode](../types/auth_SentCode.md)

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

$auth_SentCode = $MadelineProto->auth->sendCode(['allow_flashcall' => Bool, 'phone_number' => string, 'current_number' => Bool, 'api_id' => int, 'api_hash' => string, ]);
```

Or, if you're into Lua:

```
auth_SentCode = auth.sendCode({allow_flashcall=Bool, phone_number=string, current_number=Bool, api_id=int, api_hash=string, })
```

