---
title: messages.setGameScore
description: messages.setGameScore parameters, return type and example
---
## Method: messages.setGameScore  
[Back to methods index](index.md)


### Parameters:

| Name     |    Type       | Required |
|----------|---------------|----------|
|edit\_message|[Bool](../types/Bool.md) | Optional|
|peer|[InputPeer](../types/InputPeer.md) | Optional|
|id|[int](../types/int.md) | Yes|
|user\_id|[InputUser](../types/InputUser.md) | Optional|
|score|[int](../types/int.md) | Yes|


### Return type: [Updates](../types/Updates.md)

### Can bots use this method: **YES**


### Errors this method can return:

| Error    | Description   |
|----------|---------------|
|PEER_ID_INVALID|The provided peer id is invalid|
|USER_BOT_REQUIRED|This method can only be called by a bot|


### Example:


```
$MadelineProto = new \danog\MadelineProto\API();
$MadelineProto->session = 'mySession.madeline';
if (isset($token)) { // Login as a bot
    $MadelineProto->bot_login($token);
}
if (isset($number)) { // Login as a user
    $MadelineProto->phone_login($number);
    $code = readline('Enter the code you received: '); // Or do this in two separate steps in an HTTP API
    $MadelineProto->complete_phone_login($code);
}

$Updates = $MadelineProto->messages->setGameScore(['edit_message' => Bool, 'peer' => InputPeer, 'id' => int, 'user_id' => InputUser, 'score' => int, ]);
```

Or, if you're using the [PWRTelegram HTTP API](https://pwrtelegram.xyz):

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

