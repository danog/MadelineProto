---
title: channels.getChannels
description: channels.getChannels parameters, return type and example
---
## Method: channels.getChannels  
[Back to methods index](index.md)


### Parameters:

| Name     |    Type       | Required |
|----------|---------------|----------|
|id|Array of [Username, chat ID or InputChannel](../types/InputChannel.md) | Yes|


### Return type: [messages\_Chats](../types/messages_Chats.md)

### Can bots use this method: **YES**


### Errors this method can return:

| Error    | Description   |
|----------|---------------|
|CHANNEL_INVALID|The provided channel is invalid|
|CHANNEL_PRIVATE|You haven't joined this channel/supergroup|
|NEED_CHAT_INVALID|The provided chat is invalid|


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

$messages_Chats = $MadelineProto->channels->getChannels(['id' => [InputChannel], ]);
```

Or, if you're using the [PWRTelegram HTTP API](https://pwrtelegram.xyz):

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

