---
title: help.getAppUpdate
description: help.getAppUpdate parameters, return type and example
---
## Method: help.getAppUpdate  
[Back to methods index](index.md)


### Parameters:

| Name     |    Type       | Required |
|----------|:-------------:|---------:|
|device\_model|[string](../types/string.md) | Yes|
|system\_version|[string](../types/string.md) | Yes|
|app\_version|[string](../types/string.md) | Yes|
|lang\_code|[string](../types/string.md) | Yes|


### Return type: [help\_AppUpdate](../types/help_AppUpdate.md)

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

$help_AppUpdate = $MadelineProto->help->getAppUpdate(['device_model' => string, 'system_version' => string, 'app_version' => string, 'lang_code' => string, ]);
```

Or, if you're into Lua:

```
help_AppUpdate = help.getAppUpdate({device_model=string, system_version=string, app_version=string, lang_code=string, })
```

