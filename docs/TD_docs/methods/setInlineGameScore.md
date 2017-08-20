---
title: setInlineGameScore
description: Bots only. Updates game score of the specified user in the game
---
## Method: setInlineGameScore  
[Back to methods index](index.md)


YOU CANNOT USE THIS METHOD IN MADELINEPROTO


Bots only. Updates game score of the specified user in the game

### Params:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|inline\_message\_id|[string](../types/string.md) | Yes|Inline message identifier|
|edit\_message|[Bool](../types/Bool.md) | Yes|True, if message should be edited|
|user\_id|[int](../types/int.md) | Yes|User identifier|
|score|[int](../types/int.md) | Yes|New score|
|force|[Bool](../types/Bool.md) | Yes|Pass True to update the score even if it decreases. If score is 0, user will be deleted from the high scores table|


### Return type: [Ok](../types/Ok.md)

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

$Ok = $MadelineProto->setInlineGameScore(['inline_message_id' => 'string', 'edit_message' => Bool, 'user_id' => int, 'score' => int, 'force' => Bool, ]);
```

Or, if you're using the [PWRTelegram HTTP API](https://pwrtelegram.xyz):

### As a bot:

POST/GET to `https://api.pwrtelegram.xyz/botTOKEN/madeline`

Parameters:

* method - setInlineGameScore
* params - `{"inline_message_id": "string", "edit_message": Bool, "user_id": int, "score": int, "force": Bool, }`



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/setInlineGameScore`

Parameters:

inline_message_id - Json encoded string

edit_message - Json encoded Bool

user_id - Json encoded int

score - Json encoded int

force - Json encoded Bool




Or, if you're into Lua:

```
Ok = setInlineGameScore({inline_message_id='string', edit_message=Bool, user_id=int, score=int, force=Bool, })
```

