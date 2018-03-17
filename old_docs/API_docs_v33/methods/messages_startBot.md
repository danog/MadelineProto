---
title: messages.startBot
description: messages.startBot parameters, return type and example
---
## Method: messages.startBot  
[Back to methods index](index.md)


### Parameters:

| Name     |    Type       | Required |
|----------|---------------|----------|
|bot|[Username, chat ID or InputUser](../types/InputUser.md) | Optional|
|chat\_id|[Username, chat ID or InputPeer](../types/InputPeer.md) | Optional|
|start\_param|[string](../types/string.md) | Yes|


### Return type: [Updates](../types/Updates.md)

### Can bots use this method: **NO**


### Errors this method can return:

| Error    | Description   |
|----------|---------------|
|BOT_INVALID|This is not a valid bot|
|PEER_ID_INVALID|The provided peer id is invalid|
|START_PARAM_EMPTY|The start parameter is empty|
|START_PARAM_INVALID|Start parameter invalid|


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

$Updates = $MadelineProto->messages->startBot(['bot' => InputUser, 'chat_id' => InputPeer, 'start_param' => 'string', ]);
```

Or, if you're using the [PWRTelegram HTTP API](https://pwrtelegram.xyz):



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

