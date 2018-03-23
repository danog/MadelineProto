---
title: help.saveAppLog
description: Log data for developer of this app
---
## Method: help.saveAppLog  
[Back to methods index](index.md)


Log data for developer of this app

### Parameters:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|events|Array of [CLICK ME InputAppEvent](../types/InputAppEvent.md) | Yes|Event list|


### Return type: [Bool](../types/Bool.md)

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

$Bool = $MadelineProto->help->saveAppLog(['events' => [InputAppEvent, InputAppEvent], ]);
```

### [PWRTelegram HTTP API](https://pwrtelegram.xyz) example (NOT FOR MadelineProto):



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/help.saveAppLog`

Parameters:

events - Json encoded  array of InputAppEvent




Or, if you're into Lua:

```
Bool = help.saveAppLog({events={InputAppEvent}, })
```

