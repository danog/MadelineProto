---
title: messages.deleteChatUser
description: Delete a user from a chat (not supergroup)
---
## Method: messages.deleteChatUser  
[Back to methods index](index.md)


Delete a user from a chat (not supergroup)

### Parameters:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|chat\_id|[Username, chat ID, Update, Message or InputPeer](../types/InputPeer.md) | Optional|The ID of the chat|
|user\_id|[Username, chat ID, Update, Message or InputUser](../types/InputUser.md) | Optional|The user to delete (pass @me to leave the chat)|


### Return type: [messages\_StatedMessage](../types/messages_StatedMessage.md)

### Can bots use this method: **YES**


### MadelineProto Example:


```
if (!file_exists('madeline.php')) {
    copy('https://phar.madelineproto.xyz/madeline.php', 'madeline.php');
}
include 'madeline.php';

$MadelineProto = new \danog\MadelineProto\API('session.madeline');
$MadelineProto->start();

$messages_StatedMessage = $MadelineProto->messages->deleteChatUser(['chat_id' => InputPeer, 'user_id' => InputUser, ]);
```

### [PWRTelegram HTTP API](https://pwrtelegram.xyz) example (NOT FOR MadelineProto):

### As a bot:

POST/GET to `https://api.pwrtelegram.xyz/botTOKEN/madeline`

Parameters:

* method - messages.deleteChatUser
* params - `{"chat_id": InputPeer, "user_id": InputUser, }`



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/messages.deleteChatUser`

Parameters:

chat_id - Json encoded InputPeer

user_id - Json encoded InputUser




Or, if you're into Lua:

```
messages_StatedMessage = messages.deleteChatUser({chat_id=InputPeer, user_id=InputUser, })
```

### Errors this method can return:

| Error    | Description   |
|----------|---------------|
|CHAT_ID_INVALID|The provided chat id is invalid|
|PEER_ID_INVALID|The provided peer id is invalid|
|USER_NOT_PARTICIPANT|You're not a member of this supergroup/channel|


