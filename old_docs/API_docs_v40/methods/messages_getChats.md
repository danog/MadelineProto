---
title: messages.getChats
description: Get info about chats
---
## Method: messages.getChats  
[Back to methods index](index.md)


Get info about chats

### Parameters:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|id|Array of [CLICK ME InputChat](../types/InputChat.md) | Yes|The MTProto IDs of chats to fetch info about|


### Return type: [messages\_Chats](../types/messages_Chats.md)

### Can bots use this method: **YES**


### Errors this method can return:

| Error    | Description   |
|----------|---------------|
|CHAT_ID_INVALID|The provided chat id is invalid|
|PEER_ID_INVALID|The provided peer id is invalid|


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

$messages_Chats = $MadelineProto->messages->getChats(['id' => [InputChat, InputChat], ]);
```

### [PWRTelegram HTTP API](https://pwrtelegram.xyz) example (NOT FOR MadelineProto):

### As a bot:

POST/GET to `https://api.pwrtelegram.xyz/botTOKEN/madeline`

Parameters:

* method - messages.getChats
* params - `{"id": [InputChat], }`



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/messages.getChats`

Parameters:

id - Json encoded  array of InputChat




Or, if you're into Lua:

```
messages_Chats = messages.getChats({id={InputChat}, })
```

