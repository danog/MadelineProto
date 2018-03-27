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
|hash|[int](../types/int.md) | Yes|0 or $result['hash']|


### Return type: [messages\_FeaturedStickers](../types/messages_FeaturedStickers.md)

### Can bots use this method: **NO**


### MadelineProto Example:


```
if (!file_exists('madeline.php')) {
    copy('https://phar.madelineproto.xyz/madeline.php', 'madeline.php');
}
include 'madeline.php';

$MadelineProto = new \danog\MadelineProto\API('session.madeline');
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

