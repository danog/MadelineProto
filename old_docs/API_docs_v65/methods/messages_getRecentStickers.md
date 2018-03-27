---
title: messages.getRecentStickers
description: Get recent stickers
---
## Method: messages.getRecentStickers  
[Back to methods index](index.md)


Get recent stickers

### Parameters:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|attached|[Bool](../types/Bool.md) | Optional|Get stickers attached to image?|
|hash|[int](../types/int.md) | Yes|0 or $result['hash']|


### Return type: [messages\_RecentStickers](../types/messages_RecentStickers.md)

### Can bots use this method: **NO**


### MadelineProto Example:


```
if (!file_exists('madeline.php')) {
    copy('https://phar.madelineproto.xyz/madeline.php', 'madeline.php');
}
include 'madeline.php';

$MadelineProto = new \danog\MadelineProto\API('session.madeline');
$MadelineProto->start();

$messages_RecentStickers = $MadelineProto->messages->getRecentStickers(['attached' => Bool, 'hash' => int, ]);
```

### [PWRTelegram HTTP API](https://pwrtelegram.xyz) example (NOT FOR MadelineProto):



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/messages.getRecentStickers`

Parameters:

attached - Json encoded Bool

hash - Json encoded int




Or, if you're into Lua:

```
messages_RecentStickers = messages.getRecentStickers({attached=Bool, hash=int, })
```

