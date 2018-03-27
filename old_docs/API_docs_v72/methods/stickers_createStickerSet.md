---
title: stickers.createStickerSet
description: Create stickerset
---
## Method: stickers.createStickerSet  
[Back to methods index](index.md)


Create stickerset

### Parameters:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|masks|[Bool](../types/Bool.md) | Optional|Masks?|
|user\_id|[Username, chat ID, Update, Message or InputUser](../types/InputUser.md) | Optional|The user ID associated to this stickerset|
|title|[string](../types/string.md) | Yes|The stickerset title|
|short\_name|[string](../types/string.md) | Yes|The stickerset short name|
|stickers|Array of [InputStickerSetItem](../types/InputStickerSetItem.md) | Yes|The stickers to add|


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

$messages_StickerSet = $MadelineProto->stickers->createStickerSet(['masks' => Bool, 'user_id' => InputUser, 'title' => 'string', 'short_name' => 'string', 'stickers' => [InputStickerSetItem, InputStickerSetItem], ]);
```

### [PWRTelegram HTTP API](https://pwrtelegram.xyz) example (NOT FOR MadelineProto):

### As a bot:

POST/GET to `https://api.pwrtelegram.xyz/botTOKEN/madeline`

Parameters:

* method - stickers.createStickerSet
* params - `{"masks": Bool, "user_id": InputUser, "title": "string", "short_name": "string", "stickers": [InputStickerSetItem], }`



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/stickers.createStickerSet`

Parameters:

masks - Json encoded Bool

user_id - Json encoded InputUser

title - Json encoded string

short_name - Json encoded string

stickers - Json encoded  array of InputStickerSetItem




Or, if you're into Lua:

```
messages_StickerSet = stickers.createStickerSet({masks=Bool, user_id=InputUser, title='string', short_name='string', stickers={InputStickerSetItem}, })
```

### Errors this method can return:

| Error    | Description   |
|----------|---------------|
|BOT_MISSING|This method can only be run by a bot|
|PACK_SHORT_NAME_INVALID|Short pack name invalid|
|PACK_SHORT_NAME_OCCUPIED|A stickerpack with this name already exists|
|PEER_ID_INVALID|The provided peer id is invalid|
|STICKER_EMOJI_INVALID|Sticker emoji invalid|
|STICKER_FILE_INVALID|Sticker file invalid|
|STICKER_PNG_DIMENSIONS|Sticker png dimensions invalid|
|STICKERS_EMPTY|No sticker provided|
|USER_ID_INVALID|The provided user ID is invalid|


