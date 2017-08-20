---
title: sendChatAction
description: Sends notification about user activity in a chat
---
## Method: sendChatAction  
[Back to methods index](index.md)


YOU CANNOT USE THIS METHOD IN MADELINEPROTO


Sends notification about user activity in a chat

### Params:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|chat\_id|[InputPeer](../types/InputPeer.md) | Yes|Chat identifier|
|action|[SendMessageAction](../types/SendMessageAction.md) | Yes|Action description|


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

$Ok = $MadelineProto->sendChatAction(['chat_id' => InputPeer, 'action' => SendMessageAction, ]);
```

Or, if you're using the [PWRTelegram HTTP API](https://pwrtelegram.xyz):

### As a bot:

POST/GET to `https://api.pwrtelegram.xyz/botTOKEN/madeline`

Parameters:

* method - sendChatAction
* params - `{"chat_id": InputPeer, "action": SendMessageAction, }`



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/sendChatAction`

Parameters:

chat_id - Json encoded InputPeer

action - Json encoded SendMessageAction




Or, if you're into Lua:

```
Ok = sendChatAction({chat_id=InputPeer, action=SendMessageAction, })
```

