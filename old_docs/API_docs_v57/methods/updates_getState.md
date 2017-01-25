---
title: updates.getState
description: updates.getState parameters, return type and example
---
## Method: updates.getState  
[Back to methods index](index.md)




### Return type: [updates\_State](../types/updates_State.md)

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

$updates_State = $MadelineProto->updates->getState();
```