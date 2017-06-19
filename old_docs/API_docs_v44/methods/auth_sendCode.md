---
title: auth.sendCode
description: auth.sendCode parameters, return type and example
---
## Method: auth.sendCode  
[Back to methods index](index.md)


### Parameters:

| Name     |    Type       | Required |
|----------|:-------------:|---------:|
|phone\_number|[string](../types/string.md) | Yes|
|sms\_type|[int](../types/int.md) | Yes|
|api\_id|[int](../types/int.md) | Yes|
|api\_hash|[string](../types/string.md) | Yes|
|lang\_code|[string](../types/string.md) | Yes|


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

$auth_SentCode = $MadelineProto->auth->sendCode(['phone_number' => string, 'sms_type' => int, 'api_id' => int, 'api_hash' => string, 'lang_code' => string, ]);
```

Or, if you're into Lua:

```
auth_SentCode = auth.sendCode({phone_number=string, sms_type=int, api_id=int, api_hash=string, lang_code=string, })
```

