---
title: channels.setStickers
description: Set the supergroup/channel stickerpack
---
## Method: channels.setStickers  
[Back to methods index](index.md)


Set the supergroup/channel stickerpack

### Parameters:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|channel|[Username, chat ID, Update, Message or InputChannel](../types/InputChannel.md) | Optional|The channel/supergoup|
|stickerset|[InputStickerSet](../types/InputStickerSet.md) | Optional|The stickerset|


### Return type: [Bool](../types/Bool.md)

### Can bots use this method: **YES**


### MadelineProto Example:


```
if (!file_exists('madeline.php')) {
    copy('https://phar.madelineproto.xyz/madeline.php', 'madeline.php');
}
include 'madeline.php';

$MadelineProto = new \danog\MadelineProto\API('session.madeline');
$MadelineProto->start();

$Bool = $MadelineProto->channels->setStickers(['channel' => InputChannel, 'stickerset' => InputStickerSet, ]);
```

### [PWRTelegram HTTP API](https://pwrtelegram.xyz) example (NOT FOR MadelineProto):

### As a bot:

POST/GET to `https://api.pwrtelegram.xyz/botTOKEN/madeline`

Parameters:

* method - channels.setStickers
* params - `{"channel": InputChannel, "stickerset": InputStickerSet, }`



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/channels.setStickers`

Parameters:

channel - Json encoded InputChannel

stickerset - Json encoded InputStickerSet




Or, if you're into Lua:

```
Bool = channels.setStickers({channel=InputChannel, stickerset=InputStickerSet, })
```

### Errors this method can return:

| Error    | Description   |
|----------|---------------|
|CHANNEL_INVALID|The provided channel is invalid|
|PARTICIPANTS_TOO_FEW|Not enough participants|


