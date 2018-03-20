---
title: messages.getStickerSet
description: messages.getStickerSet parameters, return type and example
---
## Method: messages.getStickerSet  
[Back to methods index](index.md)


### Parameters:

| Name     |    Type       | Required |
|----------|---------------|----------|
|stickerset|[InputStickerSet](../types/InputStickerSet.md) | Optional|


### Return type: [messages\_StickerSet](../types/messages_StickerSet.md)

### Can bots use this method: **YES**


### Errors this method can return:

| Error    | Description   |
|----------|---------------|
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

$messages_StickerSet = $MadelineProto->messages->getStickerSet(['stickerset' => InputStickerSet, ]);
```

Or, if you're using the [PWRTelegram HTTP API](https://pwrtelegram.xyz):

### As a bot:

POST/GET to `https://api.pwrtelegram.xyz/botTOKEN/madeline`

Parameters:

* method - messages.getStickerSet
* params - `{"stickerset": InputStickerSet, }`



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/messages.getStickerSet`

Parameters:

stickerset - Json encoded InputStickerSet




Or, if you're into Lua:

```
messages_StickerSet = messages.getStickerSet({stickerset=InputStickerSet, })
```

