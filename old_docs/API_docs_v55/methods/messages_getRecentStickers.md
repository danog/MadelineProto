---
title: messages.getRecentStickers
description: messages.getRecentStickers parameters, return type and example
---
## Method: messages.getRecentStickers  
[Back to methods index](index.md)


### Parameters:

| Name     |    Type       | Required |
|----------|---------------|----------|
|hash|[CLICK ME int](../types/int.md) | Yes|


### Return type: [messages\_RecentStickers](../types/messages_RecentStickers.md)

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

$messages_RecentStickers = $MadelineProto->messages->getRecentStickers(['hash' => int, ]);
```

### [PWRTelegram HTTP API](https://pwrtelegram.xyz) example (NOT FOR MadelineProto):



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/messages.getRecentStickers`

Parameters:

hash - Json encoded int




Or, if you're into Lua:

```
messages_RecentStickers = messages.getRecentStickers({hash=int, })
```

