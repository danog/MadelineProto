---
title: setGameScore
description: Bots only. Updates game score of the specified user in the game
---
## Method: setGameScore  
[Back to methods index](index.md)


Bots only. Updates game score of the specified user in the game

### Params:

| Name     |    Type       | Required | Description |
|----------|:-------------:|:--------:|------------:|
|chat\_id|[long](../types/long.md) | Yes|Chat a message with the game belongs to|
|message\_id|[long](../types/long.md) | Yes|Identifier of the message|
|edit\_message|[Bool](../types/Bool.md) | Yes|True, if message should be edited|
|user\_id|[int](../types/int.md) | Yes|User identifier|
|score|[int](../types/int.md) | Yes|New score|
|force|[Bool](../types/Bool.md) | Yes|Pass True to update the score even if it decreases. If score is 0, user will be deleted from the high scores table|


### Return type: [Message](../types/Message.md)

### Example:


```
$MadelineProto = new \danog\MadelineProto\API();
if (isset($token)) {
    $this->bot_login($token);
}
if (isset($number)) {
    $sentCode = $MadelineProto->phone_login($number);
    echo 'Enter the code you received: ';
    $code = '';
    for ($x = 0; $x < $sentCode['type']['length']; $x++) {
        $code .= fgetc(STDIN);
    }
    $MadelineProto->complete_phone_login($code);
}

$Message = $MadelineProto->setGameScore(['chat_id' => long, 'message_id' => long, 'edit_message' => Bool, 'user_id' => int, 'score' => int, 'force' => Bool, ]);
```

Or, if you're into Lua:

```
Message = setGameScore({chat_id=long, message_id=long, edit_message=Bool, user_id=int, score=int, force=Bool, })
```

