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
|peer|[Username, chat ID, Update, Message or InputPeer](../types/InputPeer.md) | Optional|Where to start the bot (@me or group ID/username)|
|start\_param|[CLICK ME string](../types/string.md) | Yes|The bot's start parameter|


### Return type: [Updates](../types/Updates.md)

### Can bots use this method: **NO**


### Errors this method can return:

| Error    | Description   |
|----------|---------------|
|BOT_INVALID|This is not a valid bot|
|PEER_ID_INVALID|The provided peer id is invalid|
|START_PARAM_EMPTY|The start parameter is empty|
|START_PARAM_INVALID|Start parameter invalid|


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

$Updates = $MadelineProto->messages->startBot(['bot' => InputUser, 'peer' => InputPeer, 'start_param' => 'string', ]);
```

### [PWRTelegram HTTP API](https://pwrtelegram.xyz) example (NOT FOR MadelineProto):



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/messages.startBot`

Parameters:

bot - Json encoded InputUser

peer - Json encoded InputPeer

start_param - Json encoded string




Or, if you're into Lua:

```
Updates = messages.startBot({bot=InputUser, peer=InputPeer, start_param='string', })
```

