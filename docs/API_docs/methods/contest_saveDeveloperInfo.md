---
title: contest.saveDeveloperInfo
description: contest.saveDeveloperInfo parameters, return type and example
---
## Method: contest.saveDeveloperInfo  
[Back to methods index](index.md)


### Parameters:

| Name     |    Type       | Required |
|----------|:-------------:|---------:|
|vk\_id|[int](../types/int.md) | Required|
|name|[string](../types/string.md) | Required|
|phone\_number|[string](../types/string.md) | Required|
|age|[int](../types/int.md) | Required|
|city|[string](../types/string.md) | Required|


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

$Bool = $MadelineProto->contest->saveDeveloperInfo(['vk_id' => int, 'name' => string, 'phone_number' => string, 'age' => int, 'city' => string, ]);
```