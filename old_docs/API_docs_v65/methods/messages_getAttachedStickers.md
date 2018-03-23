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
|media|[CLICK ME InputStickeredMedia](../types/InputStickeredMedia.md) | Yes|The stickered media|


### Return type: [Vector\_of\_StickerSetCovered](../types/StickerSetCovered.md)

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

