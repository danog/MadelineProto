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
|referer|[string](../types/string.md) | Yes|Referrer|


### Return type: [help\_RecentMeUrls](../types/help_RecentMeUrls.md)

### Can bots use this method: **YES**


### MadelineProto Example:


```
if (!file_exists('madeline.php')) {
    copy('https://phar.madelineproto.xyz/madeline.php', 'madeline.php');
}
include 'madeline.php';

$MadelineProto = new \danog\MadelineProto\API('session.madeline');
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

