---
title: channels.editBanned
description: channels.editBanned parameters, return type and example
---
## Method: channels.editBanned  
[Back to methods index](index.md)


### Parameters:

| Name     |    Type       | Required |
|----------|---------------|----------|
|channel|[Username, chat ID, Update, Message or InputChannel](../types/InputChannel.md) | Optional|
|user\_id|[Username, chat ID, Update, Message or InputUser](../types/InputUser.md) | Optional|
|banned\_rights|[CLICK ME ChannelBannedRights](../types/ChannelBannedRights.md) | Yes|


### Return type: [Updates](../types/Updates.md)

### Can bots use this method: **YES**


### Errors this method can return:

| Error    | Description   |
|----------|---------------|
|CHANNEL_INVALID|The provided channel is invalid|
|CHANNEL_PRIVATE|You haven't joined this channel/supergroup|
|CHAT_ADMIN_REQUIRED|You must be an admin in this chat to do this|
|USER_ADMIN_INVALID|You're not an admin|
|USER_ID_INVALID|The provided user ID is invalid|


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

$Updates = $MadelineProto->channels->editBanned(['channel' => InputChannel, 'user_id' => InputUser, 'banned_rights' => ChannelBannedRights, ]);
```

### [PWRTelegram HTTP API](https://pwrtelegram.xyz) example (NOT FOR MadelineProto):

### As a bot:

POST/GET to `https://api.pwrtelegram.xyz/botTOKEN/madeline`

Parameters:

* method - channels.editBanned
* params - `{"channel": InputChannel, "user_id": InputUser, "banned_rights": ChannelBannedRights, }`



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/channels.editBanned`

Parameters:

channel - Json encoded InputChannel

user_id - Json encoded InputUser

banned_rights - Json encoded ChannelBannedRights




Or, if you're into Lua:

```
Updates = channels.editBanned({channel=InputChannel, user_id=InputUser, banned_rights=ChannelBannedRights, })
```

