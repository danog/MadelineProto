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
|filter|[CLICK ME ChannelParticipantsFilter](../types/ChannelParticipantsFilter.md) | Yes|Member filter|
|offset|[CLICK ME int](../types/int.md) | Yes|Offset|
|limit|[CLICK ME int](../types/int.md) | Yes|Limit|
|hash|[CLICK ME int](../types/int.md) | Yes|$MadelineProto->gen_vector_hash(ids of previously fetched participant IDs)|


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

