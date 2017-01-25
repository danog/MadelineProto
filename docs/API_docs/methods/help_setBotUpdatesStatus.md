---
title: help.setBotUpdatesStatus
description: help.setBotUpdatesStatus parameters, return type and example
---
## Method: help.setBotUpdatesStatus  
[Back to methods index](index.md)


### Parameters:

| Name     |    Type       | Required |
|----------|:-------------:|---------:|
|pending\_updates\_count|[int](../types/int.md) | Required|
|message|[string](../types/string.md) | Required|


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

$Bool = $MadelineProto->help->setBotUpdatesStatus(['pending_updates_count' => int, 'message' => string, ]);
```