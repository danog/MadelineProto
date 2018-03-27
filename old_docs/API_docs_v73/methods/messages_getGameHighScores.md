---
title: messages.getGameHighScores
description: Get high scores of a game
---
## Method: messages.getGameHighScores  
[Back to methods index](index.md)


Get high scores of a game

### Parameters:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|peer|[Username, chat ID, Update, Message or InputPeer](../types/InputPeer.md) | Optional|The chat|
|id|[int](../types/int.md) | Yes|The message ID|
|user\_id|[Username, chat ID, Update, Message or InputUser](../types/InputUser.md) | Optional|The user that set the high scores|


### Return type: [messages\_HighScores](../types/messages_HighScores.md)

### Can bots use this method: **YES**


### MadelineProto Example:


```
if (!file_exists('madeline.php')) {
    copy('https://phar.madelineproto.xyz/madeline.php', 'madeline.php');
}
include 'madeline.php';

$MadelineProto = new \danog\MadelineProto\API('session.madeline');
$MadelineProto->start();

$messages_HighScores = $MadelineProto->messages->getGameHighScores(['peer' => InputPeer, 'id' => int, 'user_id' => InputUser, ]);
```

### [PWRTelegram HTTP API](https://pwrtelegram.xyz) example (NOT FOR MadelineProto):

### As a bot:

POST/GET to `https://api.pwrtelegram.xyz/botTOKEN/madeline`

Parameters:

* method - messages.getGameHighScores
* params - `{"peer": InputPeer, "id": int, "user_id": InputUser, }`



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/messages.getGameHighScores`

Parameters:

peer - Json encoded InputPeer

id - Json encoded int

user_id - Json encoded InputUser




Or, if you're into Lua:

```
messages_HighScores = messages.getGameHighScores({peer=InputPeer, id=int, user_id=InputUser, })
```

### Errors this method can return:

| Error    | Description   |
|----------|---------------|
|PEER_ID_INVALID|The provided peer id is invalid|
|USER_BOT_REQUIRED|This method can only be called by a bot|


