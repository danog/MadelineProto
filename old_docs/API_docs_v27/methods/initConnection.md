---
title: initConnection
description: initConnection parameters, return type and example
---
## Method: initConnection  
[Back to methods index](index.md)


### Parameters:

| Name     |    Type       | Required |
|----------|:-------------:|---------:|
|api\_id|[int](../types/int.md) | Required|
|device\_model|[string](../types/string.md) | Required|
|system\_version|[string](../types/string.md) | Required|
|app\_version|[string](../types/string.md) | Required|
|lang\_code|[string](../types/string.md) | Required|
|query|[!X](../types/!X.md) | Required|


### Return type: [X](../types/X.md)

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

$X = $MadelineProto->initConnection(['api_id' => int, 'device_model' => string, 'system_version' => string, 'app_version' => string, 'lang_code' => string, 'query' => !X, ]);
```