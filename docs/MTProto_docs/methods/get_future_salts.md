---
title: get_future_salts
description: get_future_salts parameters, return type and example
---
## Method: get\_future\_salts  
[Back to methods index](index.md)


### Parameters:

| Name     |    Type       | Required |
|----------|:-------------:|---------:|
|num|[int](../types/int.md) | Required|


### Return type: [FutureSalts](../types/FutureSalts.md)

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

$FutureSalts = $MadelineProto->get->future->salts(['num' => int, ]);
```
