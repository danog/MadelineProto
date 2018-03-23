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
|stickerset|[CLICK ME InputStickerSet](../types/InputStickerSet.md) | Optional|The stickerset|


### Return type: [Bool](../types/Bool.md)

### Can bots use this method: **YES**


### Errors this method can return:

| Error    | Description   |
|----------|---------------|
|CHANNEL_INVALID|The provided channel is invalid|
|PARTICIPANTS_TOO_FEW|Not enough participants|


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

