---
title: messages.setGameScore
description: Set the game score
---
## Method: messages.setGameScore  
[Back to methods index](index.md)


Set the game score

### Parameters:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|edit\_message|[Bool](../types/Bool.md) | Optional|Should the message with the game be edited?|
|peer|[Username, chat ID, Update, Message or InputPeer](../types/InputPeer.md) | Optional|The chat where the game was sent|
|id|[int](../types/int.md) | Yes|The message ID|
|user\_id|[Username, chat ID, Update, Message or InputUser](../types/InputUser.md) | Optional|The user that set the score|
|score|[int](../types/int.md) | Yes|The score|


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

$Updates = $MadelineProto->messages->setGameScore(['edit_message' => Bool, 'peer' => InputPeer, 'id' => int, 'user_id' => InputUser, 'score' => int, ]);
```

### [PWRTelegram HTTP API](https://pwrtelegram.xyz) example (NOT FOR MadelineProto):

### As a bot:

POST/GET to `https://api.pwrtelegram.xyz/botTOKEN/madeline`

Parameters:

* method - messages.setGameScore
* params - `{"edit_message": Bool, "peer": InputPeer, "id": int, "user_id": InputUser, "score": int, }`



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/messages.setGameScore`

Parameters:

edit_message - Json encoded Bool

peer - Json encoded InputPeer

id - Json encoded int

user_id - Json encoded InputUser

score - Json encoded int




Or, if you're into Lua:

```
Updates = messages.setGameScore({edit_message=Bool, peer=InputPeer, id=int, user_id=InputUser, score=int, })
```

### Errors this method can return:

| Error    | Description   |
|----------|---------------|
|PEER_ID_INVALID|The provided peer id is invalid|
|USER_BOT_REQUIRED|This method can only be called by a bot|


