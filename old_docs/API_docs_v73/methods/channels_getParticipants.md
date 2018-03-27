---
title: channels.getParticipants
description: Get channel/supergroup participants (you should use `$MadelineProto->get_pwr_chat($id)` instead)
---
## Method: channels.getParticipants  
[Back to methods index](index.md)


Get channel/supergroup participants (you should use `$MadelineProto->get_pwr_chat($id)` instead)

### Parameters:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|channel|[Username, chat ID, Update, Message or InputChannel](../types/InputChannel.md) | Optional|The channel|
|filter|[ChannelParticipantsFilter](../types/ChannelParticipantsFilter.md) | Yes|Member filter|
|offset|[int](../types/int.md) | Yes|Offset|
|limit|[int](../types/int.md) | Yes|Limit|
|hash|[int](../types/int.md) | Yes|$MadelineProto->gen_vector_hash(ids of previously fetched participant IDs)|


### Return type: [channels\_ChannelParticipants](../types/channels_ChannelParticipants.md)

### Can bots use this method: **YES**


### MadelineProto Example:


```
if (!file_exists('madeline.php')) {
    copy('https://phar.madelineproto.xyz/madeline.php', 'madeline.php');
}
include 'madeline.php';

$MadelineProto = new \danog\MadelineProto\API('session.madeline');
$MadelineProto->start();

$channels_ChannelParticipants = $MadelineProto->channels->getParticipants(['channel' => InputChannel, 'filter' => ChannelParticipantsFilter, 'offset' => int, 'limit' => int, 'hash' => int, ]);
```

### [PWRTelegram HTTP API](https://pwrtelegram.xyz) example (NOT FOR MadelineProto):

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

### Errors this method can return:

| Error    | Description   |
|----------|---------------|
|CHANNEL_INVALID|The provided channel is invalid|
|CHANNEL_PRIVATE|You haven't joined this channel/supergroup|
|CHAT_ADMIN_REQUIRED|You must be an admin in this chat to do this|
|INPUT_CONSTRUCTOR_INVALID|The provided constructor is invalid|
|Timeout|A timeout occurred while fetching data from the bot|


