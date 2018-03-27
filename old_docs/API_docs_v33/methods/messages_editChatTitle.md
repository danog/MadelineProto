---
title: messages.editChatTitle
description: Edit the title of a normal chat (not supergroup)
---
## Method: messages.editChatTitle  
[Back to methods index](index.md)


Edit the title of a normal chat (not supergroup)

### Parameters:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|chat\_id|[Username, chat ID, Update, Message or InputPeer](../types/InputPeer.md) | Optional|The ID of the chat|
|title|[string](../types/string.md) | Yes|The new title|


### Return type: [Updates](../types/Updates.md)

### Can bots use this method: **YES**


### MadelineProto Example:


```
if (!file_exists('madeline.php')) {
    copy('https://phar.madelineproto.xyz/madeline.php', 'madeline.php');
}
include 'madeline.php';

$MadelineProto = new \danog\MadelineProto\API('session.madeline');
$MadelineProto->start();

$Updates = $MadelineProto->messages->editChatTitle(['chat_id' => InputPeer, 'title' => 'string', ]);
```

### [PWRTelegram HTTP API](https://pwrtelegram.xyz) example (NOT FOR MadelineProto):

### As a bot:

POST/GET to `https://api.pwrtelegram.xyz/botTOKEN/madeline`

Parameters:

* method - messages.editChatTitle
* params - `{"chat_id": InputPeer, "title": "string", }`



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/messages.editChatTitle`

Parameters:

chat_id - Json encoded InputPeer

title - Json encoded string




Or, if you're into Lua:

```
Updates = messages.editChatTitle({chat_id=InputPeer, title='string', })
```

### Errors this method can return:

| Error    | Description   |
|----------|---------------|
|CHAT_ID_INVALID|The provided chat id is invalid|
|NEED_CHAT_INVALID|The provided chat is invalid|


