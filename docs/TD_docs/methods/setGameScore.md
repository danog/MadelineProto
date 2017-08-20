---
title: setGameScore
description: Bots only. Updates game score of the specified user in the game
---
## Method: setGameScore  
[Back to methods index](index.md)


YOU CANNOT USE THIS METHOD IN MADELINEPROTO


Bots only. Updates game score of the specified user in the game

### Params:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|chat\_id|[InputPeer](../types/InputPeer.md) | Yes|Chat a message with the game belongs to|
|message\_id|[long](../types/long.md) | Yes|Identifier of the message|
|edit\_message|[Bool](../types/Bool.md) | Yes|True, if message should be edited|
|user\_id|[int](../types/int.md) | Yes|User identifier|
|score|[int](../types/int.md) | Yes|New score|
|force|[Bool](../types/Bool.md) | Yes|Pass True to update the score even if it decreases. If score is 0, user will be deleted from the high scores table|


### Return type: [Message](../types/Message.md)

### Example:


```
$MadelineProto = new \danog\MadelineProto\API();
if (isset($token)) { // Login as a bot
    $MadelineProto->bot_login($token);
}
if (isset($number)) { // Login as a user
    $sentCode = $MadelineProto->phone_login($number);
    echo 'Enter the code you received: ';
    $code = '';
    for ($x = 0; $x < $sentCode['type']['length']; $x++) {
        $code .= fgetc(STDIN);
    }
    $MadelineProto->complete_phone_login($code);
}

$Message = $MadelineProto->setGameScore(['chat_id' => InputPeer, 'message_id' => long, 'edit_message' => Bool, 'user_id' => int, 'score' => int, 'force' => Bool, ]);
```

Or, if you're using the [PWRTelegram HTTP API](https://pwrtelegram.xyz):

### As a bot:

POST/GET to `https://api.pwrtelegram.xyz/botTOKEN/madeline`

Parameters:

* method - setGameScore
* params - `{"chat_id": InputPeer, "message_id": long, "edit_message": Bool, "user_id": int, "score": int, "force": Bool, }`



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/setGameScore`

Parameters:

chat_id - Json encoded InputPeer

message_id - Json encoded long

edit_message - Json encoded Bool

user_id - Json encoded int

score - Json encoded int

force - Json encoded Bool




Or, if you're into Lua:

```
Message = setGameScore({chat_id=InputPeer, message_id=long, edit_message=Bool, user_id=int, score=int, force=Bool, })
```

