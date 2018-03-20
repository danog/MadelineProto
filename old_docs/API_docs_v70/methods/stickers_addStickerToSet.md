---
title: stickers.addStickerToSet
description: stickers.addStickerToSet parameters, return type and example
---
## Method: stickers.addStickerToSet  
[Back to methods index](index.md)


### Parameters:

| Name     |    Type       | Required |
|----------|---------------|----------|
|stickerset|[CLICK ME InputStickerSet](../types/InputStickerSet.md) | Optional|
|sticker|[CLICK ME InputStickerSetItem](../types/InputStickerSetItem.md) | Yes|


### Return type: [messages\_StickerSet](../types/messages_StickerSet.md)

### Can bots use this method: **YES**


### Errors this method can return:

| Error    | Description   |
|----------|---------------|
|BOT_MISSING|This method can only be run by a bot|
|STICKERSET_INVALID|The provided sticker set is invalid|


### Example:


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

$messages_StickerSet = $MadelineProto->stickers->addStickerToSet(['stickerset' => InputStickerSet, 'sticker' => InputStickerSetItem, ]);
```

Or, if you're using the [PWRTelegram HTTP API](https://pwrtelegram.xyz):

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

