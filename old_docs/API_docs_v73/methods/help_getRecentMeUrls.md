---
title: help.getRecentMeUrls
description: Get recent t.me URLs
---
## Method: help.getRecentMeUrls  
[Back to methods index](index.md)


Get recent t.me URLs

### Parameters:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|referer|[CLICK ME string](../types/string.md) | Yes|Referrer|


### Return type: [help\_RecentMeUrls](../types/help_RecentMeUrls.md)

### Can bots use this method: **YES**


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

$help_RecentMeUrls = $MadelineProto->help->getRecentMeUrls(['referer' => 'string', ]);
```

### [PWRTelegram HTTP API](https://pwrtelegram.xyz) example (NOT FOR MadelineProto):

### As a bot:

POST/GET to `https://api.pwrtelegram.xyz/botTOKEN/madeline`

Parameters:

* method - help.getRecentMeUrls
* params - `{"referer": "string", }`



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/help.getRecentMeUrls`

Parameters:

referer - Json encoded string




Or, if you're into Lua:

```
help_RecentMeUrls = help.getRecentMeUrls({referer='string', })
```

