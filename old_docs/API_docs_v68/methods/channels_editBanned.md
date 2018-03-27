---
title: channels.editBanned
description: Kick or ban a user from a channel/supergroup
---
## Method: channels.editBanned  
[Back to methods index](index.md)


Kick or ban a user from a channel/supergroup

### Parameters:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|channel|[Username, chat ID, Update, Message or InputChannel](../types/InputChannel.md) | Optional|The channel/supergroup|
|user\_id|[Username, chat ID, Update, Message or InputUser](../types/InputUser.md) | Optional|The user to kick/ban|
|banned\_rights|[ChannelBannedRights](../types/ChannelBannedRights.md) | Yes|Banned/kicked permissions|


### Return type: [Updates](../types/Updates.md)

### Can bots use this method: **YES**


### MadelineProto Example:


```
if (!file_exists('madeline.php')) {
    copy('https://phar.madelineproto.xyz/madeline.php', 'madeline.php');
}
include 'madeline.php';

$MadelineProto = new \danog\MadelineProto\API('session.madeline');
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

### Errors this method can return:

| Error    | Description   |
|----------|---------------|
|CHANNEL_INVALID|The provided channel is invalid|
|CHANNEL_PRIVATE|You haven't joined this channel/supergroup|
|CHAT_ADMIN_REQUIRED|You must be an admin in this chat to do this|
|USER_ADMIN_INVALID|You're not an admin|
|USER_ID_INVALID|The provided user ID is invalid|


