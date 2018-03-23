---
title: help.getAppChangelog
description: Get the changelog of this app
---
## Method: help.getAppChangelog  
[Back to methods index](index.md)


Get the changelog of this app



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

$help_AppChangelog = $MadelineProto->help->getAppChangelog();
```

### [PWRTelegram HTTP API](https://pwrtelegram.xyz) example (NOT FOR MadelineProto):



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/help.getAppChangelog`

Parameters:




Or, if you're into Lua:

```
help_AppChangelog = help.getAppChangelog({})
```

