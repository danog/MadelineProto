---
title: stickers.changeStickerPosition
description: stickers.changeStickerPosition parameters, return type and example
---
## Method: stickers.changeStickerPosition  
[Back to methods index](index.md)


### Parameters:

| Name     |    Type       | Required |
|----------|---------------|----------|
|sticker|[MessageMedia, Update, Message or InputDocument](../types/InputDocument.md) | Optional|
|position|[int](../types/int.md) | Yes|


### Return type: [Bool](../types/Bool.md)

### Can bots use this method: **YES**


### Errors this method can return:

| Error    | Description   |
|----------|---------------|
|BOT_MISSING|This method can only be run by a bot|
|STICKER_INVALID|The provided sticker is invalid|


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

$Bool = $MadelineProto->stickers->changeStickerPosition(['sticker' => InputDocument, 'position' => int, ]);
```

Or, if you're using the [PWRTelegram HTTP API](https://pwrtelegram.xyz):

### As a bot:

POST/GET to `https://api.pwrtelegram.xyz/botTOKEN/madeline`

Parameters:

* method - stickers.changeStickerPosition
* params - `{"sticker": InputDocument, "position": int, }`



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/stickers.changeStickerPosition`

Parameters:

sticker - Json encoded InputDocument

position - Json encoded int




Or, if you're into Lua:

```
Bool = stickers.changeStickerPosition({sticker=InputDocument, position=int, })
```

