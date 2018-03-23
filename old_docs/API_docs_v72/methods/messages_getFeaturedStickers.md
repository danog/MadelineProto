---
title: messages.getFeaturedStickers
description: Get featured stickers
---
## Method: messages.getFeaturedStickers  
[Back to methods index](index.md)


Get featured stickers

### Parameters:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|hash|[CLICK ME int](../types/int.md) | Yes|0 or $result['hash']|


### Return type: [messages\_FeaturedStickers](../types/messages_FeaturedStickers.md)

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

$messages_FeaturedStickers = $MadelineProto->messages->getFeaturedStickers(['hash' => int, ]);
```

### [PWRTelegram HTTP API](https://pwrtelegram.xyz) example (NOT FOR MadelineProto):



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/messages.getFeaturedStickers`

Parameters:

hash - Json encoded int




Or, if you're into Lua:

```
messages_FeaturedStickers = messages.getFeaturedStickers({hash=int, })
```

