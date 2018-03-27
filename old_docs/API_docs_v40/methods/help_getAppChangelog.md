---
title: help.getAppChangelog
description: Get the changelog of this app
---
## Method: help.getAppChangelog  
[Back to methods index](index.md)


Get the changelog of this app

### Parameters:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|device\_model|[string](../types/string.md) | Yes|Device model|
|system\_version|[string](../types/string.md) | Yes|System version|
|app\_version|[string](../types/string.md) | Yes|App version|
|lang\_code|[string](../types/string.md) | Yes|Language code|


### Return type: [help\_AppChangelog](../types/help_AppChangelog.md)

### Can bots use this method: **NO**


### MadelineProto Example:


```
if (!file_exists('madeline.php')) {
    copy('https://phar.madelineproto.xyz/madeline.php', 'madeline.php');
}
include 'madeline.php';

$MadelineProto = new \danog\MadelineProto\API('session.madeline');
$MadelineProto->start();

$help_AppChangelog = $MadelineProto->help->getAppChangelog(['device_model' => 'string', 'system_version' => 'string', 'app_version' => 'string', 'lang_code' => 'string', ]);
```

### [PWRTelegram HTTP API](https://pwrtelegram.xyz) example (NOT FOR MadelineProto):



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

