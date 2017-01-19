---
title: auth.sendCode
description: auth.sendCode parameters, return type and example
---
## Method: auth.sendCode  
[Back to methods index](index.md)


### Parameters:

| Name     |    Type       | Required |
|----------|:-------------:|---------:|
|phone\_number|[string](../types/string.md) | Required|
|sms\_type|[int](../types/int.md) | Required|
|api\_id|[int](../types/int.md) | Required|
|api\_hash|[string](../types/string.md) | Required|
|lang\_code|[string](../types/string.md) | Required|


### Return type: [auth\_SentCode](../types/auth_SentCode.md)

### Example:


```
$MadelineProto = new \danog\MadelineProto\API();
if (isset($token)) {
    $this->bot_login($token);
}
if (isset($number)) {
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