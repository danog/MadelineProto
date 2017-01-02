---
title: account_registerDevice
description: account_registerDevice parameters, return type and example
---
## Method: account\_registerDevice  
[Back to methods index](index.md)


### Parameters:

| Name     |    Type       | Required |
|----------|:-------------:|---------:|
|token\_type|[int](../types/int.md) | Required|
|token|[string](../types/string.md) | Required|
|device\_model|[string](../types/string.md) | Required|
|system\_version|[string](../types/string.md) | Required|
|app\_version|[string](../types/string.md) | Required|
|app\_sandbox|[Bool](../types/Bool.md) | Required|
|lang\_code|[string](../types/string.md) | Required|


### Return type: [Bool](../types/Bool.md)

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

$Bool = $MadelineProto->account->registerDevice(['token_type' => int, 'token' => string, 'device_model' => string, 'system_version' => string, 'app_version' => string, 'app_sandbox' => Bool, 'lang_code' => string, ]);
```