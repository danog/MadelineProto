---
title: channels.getMessages
description: channels.getMessages parameters, return type and example
---
## Method: channels.getMessages  
[Back to methods index](index.md)


### Parameters:

| Name     |    Type       | Required |
|----------|---------------|----------|
|channel|[InputChannel](../types/InputChannel.md) | Optional|
|id|Array of [InputMessage](../types/InputMessage.md) | Yes|


### Return type: [messages\_Messages](../types/messages_Messages.md)

### Can bots use this method: **YES**


### Errors this method can return:

| Error    | Description   |
|----------|---------------|
|CHANNEL_INVALID|The provided channel is invalid|
|CHANNEL_PRIVATE|You haven't joined this channel/supergroup|
|MESSAGE_IDS_EMPTY|No message ids were provided|


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

$messages_Messages = $MadelineProto->channels->getMessages(['channel' => InputChannel, 'id' => [InputMessage], ]);
```

Or, if you're using the [PWRTelegram HTTP API](https://pwrtelegram.xyz):

### As a bot:

POST/GET to `https://api.pwrtelegram.xyz/botTOKEN/madeline`

Parameters:

* method - channels.getMessages
* params - `{"channel": InputChannel, "id": [InputMessage], }`



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/channels.getMessages`

Parameters:

channel - Json encoded InputChannel

id - Json encoded  array of InputMessage




Or, if you're into Lua:

```
messages_Messages = channels.getMessages({channel=InputChannel, id={InputMessage}, })
```

