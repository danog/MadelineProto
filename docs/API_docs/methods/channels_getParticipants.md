---
title: channels.getParticipants
description: channels.getParticipants parameters, return type and example
---
## Method: channels.getParticipants  
[Back to methods index](index.md)


### Parameters:

| Name     |    Type       | Required |
|----------|---------------|----------|
|channel|[InputChannel](../types/InputChannel.md) | Optional|
|filter|[ChannelParticipantsFilter](../types/ChannelParticipantsFilter.md) | Yes|
|offset|[int](../types/int.md) | Yes|
|limit|[int](../types/int.md) | Yes|
|hash|[int](../types/int.md) | Yes|


### Return type: [channels\_ChannelParticipants](../types/channels_ChannelParticipants.md)

### Can bots use this method: **YES**


### Errors this method can return:

| Error    | Description   |
|----------|---------------|
|CHANNEL_INVALID|The provided channel is invalid|
|CHANNEL_PRIVATE|You haven't joined this channel/supergroup|
|CHAT_ADMIN_REQUIRED|You must be an admin in this chat to do this|
|INPUT_CONSTRUCTOR_INVALID|The provided constructor is invalid|
|Timeout|A timeout occurred while fetching data from the bot|


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

$channels_ChannelParticipants = $MadelineProto->channels->getParticipants(['channel' => InputChannel, 'filter' => ChannelParticipantsFilter, 'offset' => int, 'limit' => int, 'hash' => int, ]);
```

Or, if you're using the [PWRTelegram HTTP API](https://pwrtelegram.xyz):

### As a bot:

POST/GET to `https://api.pwrtelegram.xyz/botTOKEN/madeline`

Parameters:

* method - channels.getParticipants
* params - `{"channel": InputChannel, "filter": ChannelParticipantsFilter, "offset": int, "limit": int, "hash": int, }`



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/channels.getParticipants`

Parameters:

channel - Json encoded InputChannel

filter - Json encoded ChannelParticipantsFilter

offset - Json encoded int

limit - Json encoded int

hash - Json encoded int




Or, if you're into Lua:

```
channels_ChannelParticipants = channels.getParticipants({channel=InputChannel, filter=ChannelParticipantsFilter, offset=int, limit=int, hash=int, })
```

