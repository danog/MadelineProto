---
title: messages.getUnusedStickers
description: Get unused stickers
---
## Method: messages.getUnusedStickers  
[Back to methods index](index.md)


Get unused stickers

### Parameters:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|limit|[int](../types/int.md) | Yes|Number of results to return|


### Return type: [Vector\_of\_StickerSetCovered](../types/StickerSetCovered.md)

### Can bots use this method: **YES**


### MadelineProto Example:


```
if (!file_exists('madeline.php')) {
    copy('https://phar.madelineproto.xyz/madeline.php', 'madeline.php');
}
include 'madeline.php';

$MadelineProto = new \danog\MadelineProto\API('session.madeline');
$MadelineProto->start();

$Vector_of_StickerSetCovered = $MadelineProto->messages->getUnusedStickers(['limit' => int, ]);
```

### [PWRTelegram HTTP API](https://pwrtelegram.xyz) example (NOT FOR MadelineProto):

### As a bot:

POST/GET to `https://api.pwrtelegram.xyz/botTOKEN/madeline`

Parameters:

* method - messages.getUnusedStickers
* params - `{"limit": int, }`



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/messages.getUnusedStickers`

Parameters:

limit - Json encoded int




Or, if you're into Lua:

```
Vector_of_StickerSetCovered = messages.getUnusedStickers({limit=int, })
```

