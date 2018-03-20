---
title: stickers.removeStickerFromSet
description: stickers.removeStickerFromSet parameters, return type and example
---
## Method: stickers.removeStickerFromSet  
[Back to methods index](index.md)


### Parameters:

| Name     |    Type       | Required |
|----------|---------------|----------|
|sticker|[MessageMedia, Update, Message or InputDocument](../types/InputDocument.md) | Optional|


### Return type: [Bool](../types/Bool.md)

### Can bots use this method: **YES**


### Errors this method can return:

| Error    | Description   |
|----------|---------------|
|BOT_MISSING|This method can only be run by a bot|
|STICKER_INVALID|The provided sticker is invalid|


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

$Bool = $MadelineProto->stickers->removeStickerFromSet(['sticker' => InputDocument, ]);
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
Bool = stickers.removeStickerFromSet({sticker=InputDocument, })
```

