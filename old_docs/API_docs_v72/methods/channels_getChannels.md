---
title: channels.getChannels
description: Get info about multiple channels/supergroups
---
## Method: channels.getChannels  
[Back to methods index](index.md)


Get info about multiple channels/supergroups

### Parameters:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|id|Array of [Username, chat ID, Update, Message or InputChannel](../types/InputChannel.md) | Yes|The channel/supergroup MTProto IDs|


### Return type: [messages\_Chats](../types/messages_Chats.md)

### Can bots use this method: **YES**


### MadelineProto Example:


```
if (!file_exists('madeline.php')) {
    copy('https://phar.madelineproto.xyz/madeline.php', 'madeline.php');
}
include 'madeline.php';

$MadelineProto = new \danog\MadelineProto\API('session.madeline');
$MadelineProto->start();

$messages_Chats = $MadelineProto->channels->getChannels(['id' => [InputChannel, InputChannel], ]);
```

### [PWRTelegram HTTP API](https://pwrtelegram.xyz) example (NOT FOR MadelineProto):

### As a bot:

POST/GET to `https://api.pwrtelegram.xyz/botTOKEN/madeline`

Parameters:

* method - channels.getChannels
* params - `{"id": [InputChannel], }`



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/channels.getChannels`

Parameters:

id - Json encoded  array of InputChannel




Or, if you're into Lua:

```
messages_Chats = channels.getChannels({id={InputChannel}, })
```

### Errors this method can return:

| Error    | Description   |
|----------|---------------|
|CHANNEL_INVALID|The provided channel is invalid|
|CHANNEL_PRIVATE|You haven't joined this channel/supergroup|
|NEED_CHAT_INVALID|The provided chat is invalid|


