---
title: help.getAppChangelog
description: help.getAppChangelog parameters, return type and example
---
## Method: help.getAppChangelog  
[Back to methods index](index.md)


### Parameters:

| Name     |    Type       | Required |
|----------|---------------|----------|
|device\_model|[string](../types/string.md) | Yes|
|system\_version|[string](../types/string.md) | Yes|
|app\_version|[string](../types/string.md) | Yes|
|lang\_code|[string](../types/string.md) | Yes|


### Return type: [help\_AppChangelog](../types/help_AppChangelog.md)

### Can bots use this method: **NO**


### Example:


```
$MadelineProto = new \danog\MadelineProto\API();
if (isset($number)) { // Login as a user
    $sentCode = $MadelineProto->phone_login($number);
    echo 'Enter the code you received: ';
    $code = '';
    for ($x = 0; $x < $sentCode['type']['length']; $x++) {
        $code .= fgetc(STDIN);
    }
    $MadelineProto->complete_phone_login($code);
}

$help_AppChangelog = $MadelineProto->help->getAppChangelog(['device_model' => 'string', 'system_version' => 'string', 'app_version' => 'string', 'lang_code' => 'string', ]);
```

Or, if you're using the [PWRTelegram HTTP API](https://pwrtelegram.xyz):



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/help.getAppChangelog`

Parameters:

device_model - Json encoded string

system_version - Json encoded string

app_version - Json encoded string

lang_code - Json encoded string




Or, if you're into Lua:

```
help_AppChangelog = help.getAppChangelog({device_model='string', system_version='string', app_version='string', lang_code='string', })
```

