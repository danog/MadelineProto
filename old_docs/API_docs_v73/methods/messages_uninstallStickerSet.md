---
title: messages.uninstallStickerSet
description: Remove a sticker set
---
## Method: messages.uninstallStickerSet  
[Back to methods index](index.md)


Remove a sticker set

### Parameters:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|stickerset|[InputStickerSet](../types/InputStickerSet.md) | Optional|The sticker set to remove|


### Return type: [Bool](../types/Bool.md)

### Can bots use this method: **NO**


### MadelineProto Example:


```
if (!file_exists('madeline.php')) {
    copy('https://phar.madelineproto.xyz/madeline.php', 'madeline.php');
}
include 'madeline.php';

$MadelineProto = new \danog\MadelineProto\API('session.madeline');
$MadelineProto->start();

$Bool = $MadelineProto->messages->uninstallStickerSet(['stickerset' => InputStickerSet, ]);
```

### [PWRTelegram HTTP API](https://pwrtelegram.xyz) example (NOT FOR MadelineProto):



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/messages.uninstallStickerSet`

Parameters:

stickerset - Json encoded InputStickerSet




Or, if you're into Lua:

```
Bool = messages.uninstallStickerSet({stickerset=InputStickerSet, })
```

### Errors this method can return:

| Error    | Description   |
|----------|---------------|
|STICKERSET_INVALID|The provided sticker set is invalid|


