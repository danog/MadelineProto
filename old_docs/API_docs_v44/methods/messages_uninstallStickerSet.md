---
title: messages.uninstallStickerSet
description: messages.uninstallStickerSet parameters, return type and example
---
## Method: messages.uninstallStickerSet  
[Back to methods index](index.md)


### Parameters:

| Name     |    Type       | Required |
|----------|---------------|----------|
|stickerset|[CLICK ME InputStickerSet](../types/InputStickerSet.md) | Optional|


### Return type: [Bool](../types/Bool.md)

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

$Bool = $MadelineProto->messages->uninstallStickerSet(['stickerset' => InputStickerSet, ]);
```

Or, if you're using the [PWRTelegram HTTP API](https://pwrtelegram.xyz):



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/messages.uninstallStickerSet`

Parameters:

stickerset - Json encoded InputStickerSet




Or, if you're into Lua:

```
Bool = messages.uninstallStickerSet({stickerset=InputStickerSet, })
```

