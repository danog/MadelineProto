---
title: help_getAppChangelog
description: help_getAppChangelog parameters, return type and example
---
## Method: help\_getAppChangelog  
[Back to methods index](index.md)


### Parameters:

| Name     |    Type       | Required |
|----------|:-------------:|---------:|
|device\_model|[string](../types/string.md) | Required|
|system\_version|[string](../types/string.md) | Required|
|app\_version|[string](../types/string.md) | Required|
|lang\_code|[string](../types/string.md) | Required|


### Return type: [help\_AppChangelog](../types/help_AppChangelog.md)

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

$help_AppChangelog = $MadelineProto->help->getAppChangelog(['device_model' => string, 'system_version' => string, 'app_version' => string, 'lang_code' => string, ]);
```