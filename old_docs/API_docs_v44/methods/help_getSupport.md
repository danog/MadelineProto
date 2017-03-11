---
title: help.getSupport
description: help.getSupport parameters, return type and example
---
## Method: help.getSupport  
[Back to methods index](index.md)




### Return type: [help\_Support](../types/help_Support.md)

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

$help_Support = $MadelineProto->help->getSupport();
```

Or, if you're into Lua:

```
help_Support = help.getSupport({})
```

