---
title: stickers.addStickerToSet
description: Add sticker to stickerset
---
## Method: stickers.addStickerToSet  
[Back to methods index](index.md)


Add sticker to stickerset

### Parameters:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|stickerset|[InputStickerSet](../types/InputStickerSet.md) | Optional|The stickerset|
|sticker|[InputStickerSetItem](../types/InputStickerSetItem.md) | Yes|The sticker|


### Return type: [messages\_StickerSet](../types/messages_StickerSet.md)

### Can bots use this method: **YES**


### MadelineProto Example:


```
if (!file_exists('madeline.php')) {
    copy('https://phar.madelineproto.xyz/madeline.php', 'madeline.php');
}
include 'madeline.php';

$MadelineProto = new \danog\MadelineProto\API('session.madeline');
$MadelineProto->start();

$messages_StickerSet = $MadelineProto->stickers->addStickerToSet(['stickerset' => InputStickerSet, 'sticker' => InputStickerSetItem, ]);
```

### [PWRTelegram HTTP API](https://pwrtelegram.xyz) example (NOT FOR MadelineProto):

### As a bot:

POST/GET to `https://api.pwrtelegram.xyz/botTOKEN/madeline`

Parameters:

* method - stickers.addStickerToSet
* params - `{"stickerset": InputStickerSet, "sticker": InputStickerSetItem, }`



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/stickers.addStickerToSet`

Parameters:

stickerset - Json encoded InputStickerSet

sticker - Json encoded InputStickerSetItem




Or, if you're into Lua:

```
messages_StickerSet = stickers.addStickerToSet({stickerset=InputStickerSet, sticker=InputStickerSetItem, })
```

### Errors this method can return:

| Error    | Description   |
|----------|---------------|
|BOT_MISSING|This method can only be run by a bot|
|STICKERSET_INVALID|The provided sticker set is invalid|


