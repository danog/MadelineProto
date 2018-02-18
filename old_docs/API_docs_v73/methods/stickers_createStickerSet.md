---
title: stickers.createStickerSet
description: stickers.createStickerSet parameters, return type and example
---
## Method: stickers.createStickerSet  
[Back to methods index](index.md)


### Parameters:

| Name     |    Type       | Required |
|----------|---------------|----------|
|masks|[Bool](../types/Bool.md) | Optional|
|user\_id|[InputUser](../types/InputUser.md) | Optional|
|title|[string](../types/string.md) | Yes|
|short\_name|[string](../types/string.md) | Yes|
|stickers|Array of [InputStickerSetItem](../types/InputStickerSetItem.md) | Yes|


### Return type: [messages\_StickerSet](../types/messages_StickerSet.md)

### Can bots use this method: **YES**


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


### Example:


```
$MadelineProto = new \danog\MadelineProto\API();
$MadelineProto->session = 'mySession.madeline';
if (isset($token)) { // Login as a bot
    $MadelineProto->bot_login($token);
}
if (isset($number)) { // Login as a user
    $MadelineProto->phone_login($number);
    $code = readline('Enter the code you received: '); // Or do this in two separate steps in an HTTP API
    $MadelineProto->complete_phone_login($code);
}

$messages_StickerSet = $MadelineProto->stickers->createStickerSet(['masks' => Bool, 'user_id' => InputUser, 'title' => 'string', 'short_name' => 'string', 'stickers' => [InputStickerSetItem], ]);
```

Or, if you're using the [PWRTelegram HTTP API](https://pwrtelegram.xyz):

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

