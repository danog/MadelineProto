---
title: messages.installStickerSet
description: messages.installStickerSet parameters, return type and example
---
## Method: messages.installStickerSet  
[Back to methods index](index.md)


### Parameters:

| Name     |    Type       | Required |
|----------|---------------|----------|
|stickerset|[InputStickerSet](../types/InputStickerSet.md) | Optional|
|archived|[Bool](../types/Bool.md) | Yes|


### Return type: [messages\_StickerSetInstallResult](../types/messages_StickerSetInstallResult.md)

### Can bots use this method: **NO**


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

$messages_StickerSetInstallResult = $MadelineProto->messages->installStickerSet(['stickerset' => InputStickerSet, 'archived' => Bool, ]);
```

Or, if you're using the [PWRTelegram HTTP API](https://pwrtelegram.xyz):



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/messages.installStickerSet`

Parameters:

stickerset - Json encoded InputStickerSet

archived - Json encoded Bool




Or, if you're into Lua:

```
messages_StickerSetInstallResult = messages.installStickerSet({stickerset=InputStickerSet, archived=Bool, })
```

