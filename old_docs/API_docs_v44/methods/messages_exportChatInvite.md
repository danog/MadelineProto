---
title: messages.exportChatInvite
description: Export chat invite 
---
## Method: messages.exportChatInvite  
[Back to methods index](index.md)


Export chat invite 

### Parameters:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|chat\_id|[Username, chat ID, Update, Message or InputPeer](../types/InputPeer.md) | Optional|The chat id |


### Return type: [ExportedChatInvite](../types/ExportedChatInvite.md)

### Can bots use this method: **NO**


### MadelineProto Example:


```
if (!file_exists('madeline.php')) {
    copy('https://phar.madelineproto.xyz/madeline.php', 'madeline.php');
}
include 'madeline.php';

$MadelineProto = new \danog\MadelineProto\API('session.madeline');
$MadelineProto->start();

$ExportedChatInvite = $MadelineProto->messages->exportChatInvite(['chat_id' => InputPeer, ]);
```

### [PWRTelegram HTTP API](https://pwrtelegram.xyz) example (NOT FOR MadelineProto):



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/messages.exportChatInvite`

Parameters:

chat_id - Json encoded InputPeer




Or, if you're into Lua:

```
ExportedChatInvite = messages.exportChatInvite({chat_id=InputPeer, })
```

### Errors this method can return:

| Error    | Description   |
|----------|---------------|
|CHAT_ID_INVALID|The provided chat id is invalid|


