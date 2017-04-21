---
title: help.getAppUpdate
description: help.getAppUpdate parameters, return type and example
---
## Method: help.getAppUpdate  
[Back to methods index](index.md)




### Return type: [help\_AppUpdate](../types/help_AppUpdate.md)

### Example:


```
$MadelineProto = new \danog\MadelineProto\API();
if (isset($token)) { // Login as a bot
    $this->bot_login($token);
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

$help_AppUpdate = $MadelineProto->help->getAppUpdate();
```

Or, if you're into Lua:

```
help_AppUpdate = help.getAppUpdate({})
```

