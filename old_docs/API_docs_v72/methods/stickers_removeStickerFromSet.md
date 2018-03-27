---
title: stickers.removeStickerFromSet
description: Remove sticker from stickerset
---
## Method: stickers.removeStickerFromSet  
[Back to methods index](index.md)


Remove sticker from stickerset

### Parameters:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|sticker|[MessageMedia, Update, Message or InputDocument](../types/InputDocument.md) | Optional|The sticker to remove|


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

$messages_StickerSet = $MadelineProto->stickers->removeStickerFromSet(['sticker' => InputDocument, ]);
```

### [PWRTelegram HTTP API](https://pwrtelegram.xyz) example (NOT FOR MadelineProto):

### As a bot:

POST/GET to `https://api.pwrtelegram.xyz/botTOKEN/madeline`

Parameters:

* method - stickers.removeStickerFromSet
* params - `{"sticker": InputDocument, }`



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/stickers.removeStickerFromSet`

Parameters:

sticker - Json encoded InputDocument




Or, if you're into Lua:

```
messages_StickerSet = stickers.removeStickerFromSet({sticker=InputDocument, })
```

### Errors this method can return:

| Error    | Description   |
|----------|---------------|
|BOT_MISSING|This method can only be run by a bot|
|STICKER_INVALID|The provided sticker is invalid|


