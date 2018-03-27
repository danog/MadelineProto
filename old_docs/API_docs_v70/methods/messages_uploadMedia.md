---
title: messages.uploadMedia
description: Upload a file without sending it to anyone
---
## Method: messages.uploadMedia  
[Back to methods index](index.md)


Upload a file without sending it to anyone

### Parameters:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|peer|[Username, chat ID, Update, Message or InputPeer](../types/InputPeer.md) | Optional|Nothing|
|media|[MessageMedia, Update, Message or InputMedia](../types/InputMedia.md) | Optional|The media to upload|


### Return type: [MessageMedia](../types/MessageMedia.md)

### Can bots use this method: **YES**


### MadelineProto Example:


```
if (!file_exists('madeline.php')) {
    copy('https://phar.madelineproto.xyz/madeline.php', 'madeline.php');
}
include 'madeline.php';

$MadelineProto = new \danog\MadelineProto\API('session.madeline');
$MadelineProto->start();

$MessageMedia = $MadelineProto->messages->uploadMedia(['peer' => InputPeer, 'media' => InputMedia, ]);
```

### [PWRTelegram HTTP API](https://pwrtelegram.xyz) example (NOT FOR MadelineProto):

### As a bot:

POST/GET to `https://api.pwrtelegram.xyz/botTOKEN/madeline`

Parameters:

* method - messages.uploadMedia
* params - `{"peer": InputPeer, "media": InputMedia, }`



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/messages.uploadMedia`

Parameters:

peer - Json encoded InputPeer

media - Json encoded InputMedia




Or, if you're into Lua:

```
MessageMedia = messages.uploadMedia({peer=InputPeer, media=InputMedia, })
```

### Errors this method can return:

| Error    | Description   |
|----------|---------------|
|BOT_MISSING|This method can only be run by a bot|
|MEDIA_INVALID|Media invalid|
|PEER_ID_INVALID|The provided peer id is invalid|


