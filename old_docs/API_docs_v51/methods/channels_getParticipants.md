---
title: channels.getParticipants
description: channels.getParticipants parameters, return type and example
---
## Method: channels.getParticipants  
[Back to methods index](index.md)


### Parameters:

| Name     |    Type       | Required |
|----------|---------------|----------|
|channel|[Username, chat ID, Update, Message or InputChannel](../types/InputChannel.md) | Optional|
|filter|[CLICK ME ChannelParticipantsFilter](../types/ChannelParticipantsFilter.md) | Yes|
|offset|[CLICK ME int](../types/int.md) | Yes|
|limit|[CLICK ME int](../types/int.md) | Yes|


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

$channels_ChannelParticipants = $MadelineProto->channels->getParticipants(['channel' => InputChannel, 'filter' => ChannelParticipantsFilter, 'offset' => int, 'limit' => int, ]);
```

Or, if you're using the [PWRTelegram HTTP API](https://pwrtelegram.xyz):

### As a bot:

POST/GET to `https://api.pwrtelegram.xyz/botTOKEN/madeline`

Parameters:

* method - channels.getParticipants
* params - `{"channel": InputChannel, "filter": ChannelParticipantsFilter, "offset": int, "limit": int, }`



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/channels.getParticipants`

Parameters:

channel - Json encoded InputChannel

filter - Json encoded ChannelParticipantsFilter

offset - Json encoded int

limit - Json encoded int




Or, if you're into Lua:

```
channels_ChannelParticipants = channels.getParticipants({channel=InputChannel, filter=ChannelParticipantsFilter, offset=int, limit=int, })
```

