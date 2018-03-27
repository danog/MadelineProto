---
title: messages.getAttachedStickers
description: Get stickers attachable to images
---
## Method: messages.getAttachedStickers  
[Back to methods index](index.md)


Get stickers attachable to images

### Parameters:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|media|[InputStickeredMedia](../types/InputStickeredMedia.md) | Yes|The stickered media|


### Return type: [Vector\_of\_StickerSetCovered](../types/StickerSetCovered.md)

### Can bots use this method: **NO**


### MadelineProto Example:


```
if (!file_exists('madeline.php')) {
    copy('https://phar.madelineproto.xyz/madeline.php', 'madeline.php');
}
include 'madeline.php';

$MadelineProto = new \danog\MadelineProto\API('session.madeline');
$MadelineProto->start();

$Vector_of_StickerSetCovered = $MadelineProto->messages->getAttachedStickers(['media' => InputStickeredMedia, ]);
```

### [PWRTelegram HTTP API](https://pwrtelegram.xyz) example (NOT FOR MadelineProto):



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/messages.getAttachedStickers`

Parameters:

media - Json encoded InputStickeredMedia




Or, if you're into Lua:

```
Vector_of_StickerSetCovered = messages.getAttachedStickers({media=InputStickeredMedia, })
```

