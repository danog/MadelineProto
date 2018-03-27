---
title: geochats.editChatPhoto
description: Edit geochat photo
---
## Method: geochats.editChatPhoto  
[Back to methods index](index.md)


Edit geochat photo

### Parameters:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|peer|[InputGeoChat](../types/InputGeoChat.md) | Yes|The geochat|
|photo|[InputChatPhoto](../types/InputChatPhoto.md) | Optional|The new photo|


### Return type: [geochats\_StatedMessage](../types/geochats_StatedMessage.md)

### Can bots use this method: **YES**


### MadelineProto Example:


```
if (!file_exists('madeline.php')) {
    copy('https://phar.madelineproto.xyz/madeline.php', 'madeline.php');
}
include 'madeline.php';

$MadelineProto = new \danog\MadelineProto\API('session.madeline');
$MadelineProto->start();

$geochats_StatedMessage = $MadelineProto->geochats->editChatPhoto(['peer' => InputGeoChat, 'photo' => InputChatPhoto, ]);
```

### [PWRTelegram HTTP API](https://pwrtelegram.xyz) example (NOT FOR MadelineProto):

### As a bot:

POST/GET to `https://api.pwrtelegram.xyz/botTOKEN/madeline`

Parameters:

* method - geochats.editChatPhoto
* params - `{"peer": InputGeoChat, "photo": InputChatPhoto, }`



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/geochats.editChatPhoto`

Parameters:

peer - Json encoded InputGeoChat

photo - Json encoded InputChatPhoto




Or, if you're into Lua:

```
geochats_StatedMessage = geochats.editChatPhoto({peer=InputGeoChat, photo=InputChatPhoto, })
```

