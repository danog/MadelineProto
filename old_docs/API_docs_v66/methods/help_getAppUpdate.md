---
title: help.getAppUpdate
description: Get info about app updates
---
## Method: help.getAppUpdate  
[Back to methods index](index.md)


Get info about app updates



### Return type: [help\_AppUpdate](../types/help_AppUpdate.md)

### Can bots use this method: **NO**


### MadelineProto Example:


```
if (!file_exists('madeline.php')) {
    copy('https://phar.madelineproto.xyz/madeline.php', 'madeline.php');
}
include 'madeline.php';

$MadelineProto = new \danog\MadelineProto\API('session.madeline');
$MadelineProto->start();

$help_AppUpdate = $MadelineProto->help->getAppUpdate();
```

### [PWRTelegram HTTP API](https://pwrtelegram.xyz) example (NOT FOR MadelineProto):



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/help.getAppUpdate`

Parameters:




Or, if you're into Lua:

```
help_AppUpdate = help.getAppUpdate({})
```

