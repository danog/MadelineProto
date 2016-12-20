---
title: help_getConfig
description: help_getConfig parameters, return type and example
---
## Method: help\_getConfig  
[Back to methods index](index.md)




### Return type: [Config](../types/Config.md)

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

$Config = $MadelineProto->help_getConfig();
```