---
title: channels.editAdmin
description: Edit admin permissions of a user in a channel/supergroup
---
## Method: channels.editAdmin  
[Back to methods index](index.md)


Edit admin permissions of a user in a channel/supergroup

### Parameters:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|channel|[Username, chat ID, Update, Message or InputChannel](../types/InputChannel.md) | Optional|The channel|
|user\_id|[Username, chat ID, Update, Message or InputUser](../types/InputUser.md) | Optional|The user|
|role|[ChannelParticipantRole](../types/ChannelParticipantRole.md) | Yes|User role|


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

$Bool = $MadelineProto->channels->editAdmin(['channel' => InputChannel, 'user_id' => InputUser, 'role' => ChannelParticipantRole, ]);
```

### [PWRTelegram HTTP API](https://pwrtelegram.xyz) example (NOT FOR MadelineProto):

### As a bot:

POST/GET to `https://api.pwrtelegram.xyz/botTOKEN/madeline`

Parameters:

* method - channels.editAdmin
* params - `{"channel": InputChannel, "user_id": InputUser, "role": ChannelParticipantRole, }`



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/channels.editAdmin`

Parameters:

channel - Json encoded InputChannel

user_id - Json encoded InputUser

role - Json encoded ChannelParticipantRole




Or, if you're into Lua:

```
Bool = channels.editAdmin({channel=InputChannel, user_id=InputUser, role=ChannelParticipantRole, })
```

### Errors this method can return:

| Error    | Description   |
|----------|---------------|
|ADMINS_TOO_MUCH|Too many admins|
|BOT_CHANNELS_NA|Bots can't edit admin privileges|
|CHANNEL_INVALID|The provided channel is invalid|
|CHAT_ADMIN_REQUIRED|You must be an admin in this chat to do this|
|USER_CREATOR|You can't leave this channel, because you're its creator|
|USER_ID_INVALID|The provided user ID is invalid|
|USER_NOT_MUTUAL_CONTACT|The provided user is not a mutual contact|
|CHAT_ADMIN_INVITE_REQUIRED|You do not have the rights to do this|
|RIGHT_FORBIDDEN|Your admin rights do not allow you to do this|
|USER_PRIVACY_RESTRICTED|The user's privacy settings do not allow you to do this|


