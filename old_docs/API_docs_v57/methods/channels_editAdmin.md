---
title: channels.editAdmin
description: channels.editAdmin parameters, return type and example
---
## Method: channels.editAdmin  
[Back to methods index](index.md)


### Parameters:

| Name     |    Type       | Required |
|----------|---------------|----------|
|channel|[Username, chat ID or InputChannel](../types/InputChannel.md) | Optional|
|user\_id|[Username, chat ID or InputUser](../types/InputUser.md) | Optional|
|role|[ChannelParticipantRole](../types/ChannelParticipantRole.md) | Yes|


### Return type: [Updates](../types/Updates.md)

### Can bots use this method: **YES**


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

$Updates = $MadelineProto->channels->editAdmin(['channel' => InputChannel, 'user_id' => InputUser, 'role' => ChannelParticipantRole, ]);
```

Or, if you're using the [PWRTelegram HTTP API](https://pwrtelegram.xyz):

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
Updates = channels.editAdmin({channel=InputChannel, user_id=InputUser, role=ChannelParticipantRole, })
```

