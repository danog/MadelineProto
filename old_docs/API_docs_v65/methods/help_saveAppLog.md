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
|events|Array of [InputAppEvent](../types/InputAppEvent.md) | Yes|Event list|


### Return type: [Bool](../types/Bool.md)

### Can bots use this method: **NO**


### MadelineProto Example:


```
if (!file_exists('madeline.php')) {
    copy('https://phar.madelineproto.xyz/madeline.php', 'madeline.php');
}
include 'madeline.php';

$MadelineProto = new \danog\MadelineProto\API('session.madeline');
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

