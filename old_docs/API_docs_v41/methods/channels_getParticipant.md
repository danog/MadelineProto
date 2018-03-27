---
title: channels.getParticipant
description: Get info about a certain channel/supergroup participant
---
## Method: channels.getParticipant  
[Back to methods index](index.md)


Get info about a certain channel/supergroup participant

### Parameters:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|channel|[Username, chat ID, Update, Message or InputChannel](../types/InputChannel.md) | Optional|The channel/supergroup|
|user\_id|[Username, chat ID, Update, Message or InputUser](../types/InputUser.md) | Optional|The user to fetch info about|


### Return type: [channels\_ChannelParticipant](../types/channels_ChannelParticipant.md)

### Can bots use this method: **YES**


### MadelineProto Example:


```
if (!file_exists('madeline.php')) {
    copy('https://phar.madelineproto.xyz/madeline.php', 'madeline.php');
}
include 'madeline.php';

$MadelineProto = new \danog\MadelineProto\API('session.madeline');
$MadelineProto->start();

$channels_ChannelParticipant = $MadelineProto->channels->getParticipant(['channel' => InputChannel, 'user_id' => InputUser, ]);
```

### [PWRTelegram HTTP API](https://pwrtelegram.xyz) example (NOT FOR MadelineProto):

### As a bot:

POST/GET to `https://api.pwrtelegram.xyz/botTOKEN/madeline`

Parameters:

* method - channels.getParticipant
* params - `{"channel": InputChannel, "user_id": InputUser, }`



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/channels.getParticipant`

Parameters:

channel - Json encoded InputChannel

user_id - Json encoded InputUser




Or, if you're into Lua:

```
channels_ChannelParticipant = channels.getParticipant({channel=InputChannel, user_id=InputUser, })
```

### Errors this method can return:

| Error    | Description   |
|----------|---------------|
|CHANNEL_INVALID|The provided channel is invalid|
|CHANNEL_PRIVATE|You haven't joined this channel/supergroup|
|CHAT_ADMIN_REQUIRED|You must be an admin in this chat to do this|
|USER_ID_INVALID|The provided user ID is invalid|
|USER_NOT_PARTICIPANT|You're not a member of this supergroup/channel|


