---
title: channels.updateUsername
description: Update the username of a supergroup/channel
---
## Method: channels.updateUsername  
[Back to methods index](index.md)


Update the username of a supergroup/channel

### Parameters:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|channel|[Username, chat ID, Update, Message or InputChannel](../types/InputChannel.md) | Optional|The channel/supergroup|
|username|[CLICK ME string](../types/string.md) | Yes|The new username|


### Return type: [Bool](../types/Bool.md)

### Can bots use this method: **NO**


### Errors this method can return:

| Error    | Description   |
|----------|---------------|
|CHANNEL_INVALID|The provided channel is invalid|
|CHANNELS_ADMIN_PUBLIC_TOO_MUCH|You're admin of too many public channels, make some channels private to change the username of this channel|
|CHAT_ADMIN_REQUIRED|You must be an admin in this chat to do this|
|USERNAME_INVALID|The provided username is not valid|
|USERNAME_OCCUPIED|The provided username is already occupied|


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

$Bool = $MadelineProto->channels->updateUsername(['channel' => InputChannel, 'username' => 'string', ]);
```

### [PWRTelegram HTTP API](https://pwrtelegram.xyz) example (NOT FOR MadelineProto):



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/channels.updateUsername`

Parameters:

channel - Json encoded InputChannel

username - Json encoded string




Or, if you're into Lua:

```
Bool = channels.updateUsername({channel=InputChannel, username='string', })
```

