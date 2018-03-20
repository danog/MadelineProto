---
title: messages.getMessages
description: Get messages
---
## Method: messages.getMessages  
[Back to methods index](index.md)


Get messages

### Parameters:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|id|Array of [Message ID or InputMessage](../types/InputMessage.md) | Yes|The IDs of messages to fetch (only for users and normal groups)|


### Return type: [messages\_Messages](../types/messages_Messages.md)

### Can bots use this method: **YES**


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

$messages_Messages = $MadelineProto->messages->getMessages(['id' => [InputMessage, InputMessage], ]);
```

### [PWRTelegram HTTP API](https://pwrtelegram.xyz) example (NOT FOR MadelineProto):

### As a bot:

POST/GET to `https://api.pwrtelegram.xyz/botTOKEN/madeline`

Parameters:

* method - messages.getMessages
* params - `{"id": [InputMessage], }`



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/messages.getMessages`

Parameters:

id - Json encoded  array of InputMessage




Or, if you're into Lua:

```
messages_Messages = messages.getMessages({id={InputMessage}, })
```

