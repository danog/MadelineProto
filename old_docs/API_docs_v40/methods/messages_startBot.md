---
title: messages.startBot
description: Start a bot
---
## Method: messages.startBot  
[Back to methods index](index.md)


Start a bot

### Parameters:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|bot|[Username, chat ID, Update, Message or InputUser](../types/InputUser.md) | Optional|The bot's ID or username|
|chat\_id|[Username, chat ID, Update, Message or InputPeer](../types/InputPeer.md) | Optional|Chat ID|
|start\_param|[string](../types/string.md) | Yes|The bot's start parameter|


### Return type: [Updates](../types/Updates.md)

### Can bots use this method: **NO**


### MadelineProto Example:


```
if (!file_exists('madeline.php')) {
    copy('https://phar.madelineproto.xyz/madeline.php', 'madeline.php');
}
include 'madeline.php';

$MadelineProto = new \danog\MadelineProto\API('session.madeline');
$MadelineProto->start();

$Updates = $MadelineProto->messages->startBot(['bot' => InputUser, 'chat_id' => InputPeer, 'start_param' => 'string', ]);
```

### [PWRTelegram HTTP API](https://pwrtelegram.xyz) example (NOT FOR MadelineProto):



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/messages.startBot`

Parameters:

bot - Json encoded InputUser

chat_id - Json encoded InputPeer

start_param - Json encoded string




Or, if you're into Lua:

```
Updates = messages.startBot({bot=InputUser, chat_id=InputPeer, start_param='string', })
```

### Errors this method can return:

| Error    | Description   |
|----------|---------------|
|BOT_INVALID|This is not a valid bot|
|PEER_ID_INVALID|The provided peer id is invalid|
|START_PARAM_EMPTY|The start parameter is empty|
|START_PARAM_INVALID|Start parameter invalid|


