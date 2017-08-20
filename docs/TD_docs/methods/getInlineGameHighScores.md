---
title: getInlineGameHighScores
description: Bots only. Returns game high scores and some part of the score table around of the specified user in the game
---
## Method: getInlineGameHighScores  
[Back to methods index](index.md)


YOU CANNOT USE THIS METHOD IN MADELINEPROTO


Bots only. Returns game high scores and some part of the score table around of the specified user in the game

### Params:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|inline\_message\_id|[string](../types/string.md) | Yes|Inline message identifier|
|user\_id|[int](../types/int.md) | Yes|User identifier|


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

$GameHighScores = $MadelineProto->getInlineGameHighScores(['inline_message_id' => 'string', 'user_id' => int, ]);
```

Or, if you're using the [PWRTelegram HTTP API](https://pwrtelegram.xyz):

### As a bot:

POST/GET to `https://api.pwrtelegram.xyz/botTOKEN/madeline`

Parameters:

* method - getInlineGameHighScores
* params - `{"inline_message_id": "string", "user_id": int, }`



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/getInlineGameHighScores`

Parameters:

inline_message_id - Json encoded string

user_id - Json encoded int




Or, if you're into Lua:

```
GameHighScores = getInlineGameHighScores({inline_message_id='string', user_id=int, })
```

