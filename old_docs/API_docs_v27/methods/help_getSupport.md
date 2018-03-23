---
title: help.getSupport
description: Get info of support user
---
## Method: help.getSupport  
[Back to methods index](index.md)


Get info of support user



### Return type: [help\_Support](../types/help_Support.md)

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

$help_Support = $MadelineProto->help->getSupport();
```

### [PWRTelegram HTTP API](https://pwrtelegram.xyz) example (NOT FOR MadelineProto):



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/help.getSupport`

Parameters:




Or, if you're into Lua:

```
help_Support = help.getSupport({})
```

