---
title: channels.updateUsername
description: channels.updateUsername parameters, return type and example
---
## Method: channels.updateUsername  
[Back to methods index](index.md)


### Parameters:

| Name     |    Type       | Required |
|----------|---------------|----------|
|channel|[Username, chat ID, Update, Message or InputChannel](../types/InputChannel.md) | Optional|
|username|[CLICK ME string](../types/string.md) | Yes|


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

$Bool = $MadelineProto->channels->updateUsername(['channel' => InputChannel, 'username' => 'string', ]);
```

Or, if you're using the [PWRTelegram HTTP API](https://pwrtelegram.xyz):



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/channels.updateUsername`

Parameters:

channel - Json encoded InputChannel

username - Json encoded string




Or, if you're into Lua:

```
Bool = channels.updateUsername({channel=InputChannel, username='string', })
```

