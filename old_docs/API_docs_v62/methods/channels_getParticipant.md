---
title: channels.getParticipant
description: channels.getParticipant parameters, return type and example
---
## Method: channels.getParticipant  
[Back to methods index](index.md)


### Parameters:

| Name     |    Type       | Required |
|----------|---------------|----------|
|channel|[InputChannel](../types/InputChannel.md) | Optional|
|user\_id|[InputUser](../types/InputUser.md) | Optional|


### Return type: [channels\_ChannelParticipant](../types/channels_ChannelParticipant.md)

### Can bots use this method: **YES**


### Errors this method can return:

| Error    | Description   |
|----------|---------------|
|CHANNEL_INVALID|The provided channel is invalid|
|CHANNEL_PRIVATE|You haven't joined this channel/supergroup|
|CHAT_ADMIN_REQUIRED|You must be an admin in this chat to do this|
|USER_ID_INVALID|The provided user ID is invalid|
|USER_NOT_PARTICIPANT|You're not a member of this supergroup/channel|


### Example:


```
$MadelineProto = new \danog\MadelineProto\API();
$MadelineProto->session = 'mySession.madeline';
if (isset($token)) { // Login as a bot
    $MadelineProto->bot_login($token);
}
if (isset($number)) { // Login as a user
    $MadelineProto->phone_login($number);
    $code = readline('Enter the code you received: '); // Or do this in two separate steps in an HTTP API
    $MadelineProto->complete_phone_login($code);
}

$channels_ChannelParticipant = $MadelineProto->channels->getParticipant(['channel' => InputChannel, 'user_id' => InputUser, ]);
```

Or, if you're using the [PWRTelegram HTTP API](https://pwrtelegram.xyz):

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

