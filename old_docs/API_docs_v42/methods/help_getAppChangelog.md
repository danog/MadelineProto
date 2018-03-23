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
|device\_model|[CLICK ME string](../types/string.md) | Yes||
|system\_version|[CLICK ME string](../types/string.md) | Yes||
|app\_version|[CLICK ME string](../types/string.md) | Yes||
|lang\_code|[CLICK ME string](../types/string.md) | Yes||


### Return type: [help\_AppChangelog](../types/help_AppChangelog.md)

### Can bots use this method: **NO**


### MadelineProto Example:


```
if (!file_exists('madeline.php')) {
    copy('https://phar.madelineproto.xyz/madeline.php', 'madeline.php');
}
include 'madeline.php';

// !!! This API id/API hash combination will not work !!!
// !!! You must get your own @ my.telegram.org !!!
$api_id = 0;
$api_hash = '';

$MadelineProto = new \danog\MadelineProto\API('session.madeline', ['app_info' => ['api_id' => $api_id, 'api_hash' => $api_hash]]);
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

