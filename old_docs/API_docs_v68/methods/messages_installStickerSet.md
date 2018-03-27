---
title: messages.installStickerSet
description: Add a sticker set
---
## Method: messages.installStickerSet  
[Back to methods index](index.md)


Add a sticker set

### Parameters:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|stickerset|[InputStickerSet](../types/InputStickerSet.md) | Optional|The sticker set to add|
|archived|[Bool](../types/Bool.md) | Yes|Archive this set?|


### Return type: [messages\_StickerSetInstallResult](../types/messages_StickerSetInstallResult.md)

### Can bots use this method: **NO**


### MadelineProto Example:


```
if (!file_exists('madeline.php')) {
    copy('https://phar.madelineproto.xyz/madeline.php', 'madeline.php');
}
include 'madeline.php';

$MadelineProto = new \danog\MadelineProto\API('session.madeline');
$MadelineProto->start();

$messages_StickerSetInstallResult = $MadelineProto->messages->installStickerSet(['stickerset' => InputStickerSet, 'archived' => Bool, ]);
```

### [PWRTelegram HTTP API](https://pwrtelegram.xyz) example (NOT FOR MadelineProto):



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/messages.installStickerSet`

Parameters:

stickerset - Json encoded InputStickerSet

archived - Json encoded Bool




Or, if you're into Lua:

```
messages_StickerSetInstallResult = messages.installStickerSet({stickerset=InputStickerSet, archived=Bool, })
```

### Errors this method can return:

| Error    | Description   |
|----------|---------------|
|STICKERSET_INVALID|The provided sticker set is invalid|


