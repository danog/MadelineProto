---
title: messages.setTyping
description: Change typing status
---
## Method: messages.setTyping  
[Back to methods index](index.md)


Change typing status

### Parameters:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|peer|[Username, chat ID, Update, Message or InputPeer](../types/InputPeer.md) | Optional|Where to change typing status|
|action|[SendMessageAction](../types/SendMessageAction.md) | Yes|Typing status|


### Return type: [Bool](../types/Bool.md)

### Can bots use this method: **YES**


### MadelineProto Example:


```
if (!file_exists('madeline.php')) {
    copy('https://phar.madelineproto.xyz/madeline.php', 'madeline.php');
}
include 'madeline.php';

$MadelineProto = new \danog\MadelineProto\API('session.madeline');
$MadelineProto->start();

$Bool = $MadelineProto->messages->setTyping(['peer' => InputPeer, 'action' => SendMessageAction, ]);
```

### [PWRTelegram HTTP API](https://pwrtelegram.xyz) example (NOT FOR MadelineProto):

### As a bot:

POST/GET to `https://api.pwrtelegram.xyz/botTOKEN/madeline`

Parameters:

* method - messages.setTyping
* params - `{"peer": InputPeer, "action": SendMessageAction, }`



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/messages.setTyping`

Parameters:

peer - Json encoded InputPeer

action - Json encoded SendMessageAction




Or, if you're into Lua:

```
Bool = messages.setTyping({peer=InputPeer, action=SendMessageAction, })
```

### Errors this method can return:

| Error    | Description   |
|----------|---------------|
|CHANNEL_INVALID|The provided channel is invalid|
|CHANNEL_PRIVATE|You haven't joined this channel/supergroup|
|CHAT_ID_INVALID|The provided chat id is invalid|
|PEER_ID_INVALID|The provided peer id is invalid|
|USER_BANNED_IN_CHANNEL|You're banned from sending messages in supergroups/channels|
|USER_IS_BLOCKED|User is blocked|
|USER_IS_BOT|Bots can't send messages to other bots|
|CHAT_WRITE_FORBIDDEN|You can't write in this chat|


