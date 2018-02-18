---
title: channels.getChannels
description: channels.getChannels parameters, return type and example
---
## Method: channels.getChannels  
[Back to methods index](index.md)


### Parameters:

| Name     |    Type       | Required |
|----------|---------------|----------|
|id|Array of [InputChannel](../types/InputChannel.md) | Yes|


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

