---
title: messages.getGameHighScores
description: messages.getGameHighScores parameters, return type and example
---
## Method: messages.getGameHighScores  
[Back to methods index](index.md)


### Parameters:

| Name     |    Type       | Required |
|----------|---------------|----------|
|peer|[InputPeer](../types/InputPeer.md) | Yes|
|id|[int](../types/int.md) | Yes|
|user\_id|[InputUser](../types/InputUser.md) | Yes|


### Return type: [messages\_HighScores](../types/messages_HighScores.md)

### Can bots use this method: **YES**


### Errors this method can return:

| Error    | Description   |
|----------|---------------|
|PEER_ID_INVALID|The provided peer id is invalid|
|USER_BOT_REQUIRED|This method can only be called by a bot|


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

$messages_HighScores = $MadelineProto->messages->getGameHighScores(['peer' => InputPeer, 'id' => int, 'user_id' => InputUser, ]);
```

Or, if you're using the [PWRTelegram HTTP API](https://pwrtelegram.xyz):

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

