---
title: getGameHighScores
description: Bots only. Returns game high scores and some part of the score table around of the specified user in the game
---
## Method: getGameHighScores  
[Back to methods index](index.md)


YOU CANNOT USE THIS METHOD IN MADELINEPROTO


Bots only. Returns game high scores and some part of the score table around of the specified user in the game

### Params:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|chat\_id|[InputPeer](../types/InputPeer.md) | Yes|Chat a message with the game belongs to|
|message\_id|[long](../types/long.md) | Yes|Identifier of the message|
|user\_id|[int](../types/int.md) | Yes|User identifie|


### Return type: [GameHighScores](../types/GameHighScores.md)

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

$GameHighScores = $MadelineProto->getGameHighScores(['chat_id' => InputPeer, 'message_id' => long, 'user_id' => int, ]);
```

Or, if you're using the [PWRTelegram HTTP API](https://pwrtelegram.xyz):

### As a bot:

POST/GET to `https://api.pwrtelegram.xyz/botTOKEN/madeline`

Parameters:

* method - getGameHighScores
* params - `{"chat_id": InputPeer, "message_id": long, "user_id": int, }`



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/getGameHighScores`

Parameters:

chat_id - Json encoded InputPeer

message_id - Json encoded long

user_id - Json encoded int




Or, if you're into Lua:

```
GameHighScores = getGameHighScores({chat_id=InputPeer, message_id=long, user_id=int, })
```

