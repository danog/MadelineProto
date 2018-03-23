---
title: channels.editPhoto
description: Edit the photo of a supergroup/channel
---
## Method: channels.editPhoto  
[Back to methods index](index.md)


Edit the photo of a supergroup/channel

### Parameters:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|channel|[Username, chat ID, Update, Message or InputChannel](../types/InputChannel.md) | Optional|The channel|
|photo|[CLICK ME InputChatPhoto](../types/InputChatPhoto.md) | Optional|The new photo|


### Return type: [Updates](../types/Updates.md)

### Can bots use this method: **YES**


### Errors this method can return:

| Error    | Description   |
|----------|---------------|
|CHANNEL_INVALID|The provided channel is invalid|
|CHAT_ADMIN_REQUIRED|You must be an admin in this chat to do this|
|PHOTO_INVALID|Photo invalid|


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

$Updates = $MadelineProto->channels->editPhoto(['channel' => InputChannel, 'photo' => InputChatPhoto, ]);
```

### [PWRTelegram HTTP API](https://pwrtelegram.xyz) example (NOT FOR MadelineProto):

### As a bot:

POST/GET to `https://api.pwrtelegram.xyz/botTOKEN/madeline`

Parameters:

* method - channels.editPhoto
* params - `{"channel": InputChannel, "photo": InputChatPhoto, }`



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/channels.editPhoto`

Parameters:

channel - Json encoded InputChannel

photo - Json encoded InputChatPhoto




Or, if you're into Lua:

```
Updates = channels.editPhoto({channel=InputChannel, photo=InputChatPhoto, })
```

