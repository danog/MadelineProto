---
title: sendBotStartMessage
description: Invites bot to a chat (if it is not in the chat) and send /start to it. Bot can't be invited to a private chat other than chat with the bot. Bots can't be invited to broadcast channel chats and secret chats. Returns sent message. UpdateChatTopMessage will not be sent, so returned message should be used to update chat top message
---
## Method: sendBotStartMessage  
[Back to methods index](index.md)


YOU CANNOT USE THIS METHOD IN MADELINEPROTO


Invites bot to a chat (if it is not in the chat) and send /start to it. Bot can't be invited to a private chat other than chat with the bot. Bots can't be invited to broadcast channel chats and secret chats. Returns sent message. UpdateChatTopMessage will not be sent, so returned message should be used to update chat top message

### Params:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|bot\_user\_id|[int](../types/int.md) | Yes|Identifier of the bot|
|chat\_id|[InputPeer](../types/InputPeer.md) | Yes|Identifier of the chat|
|parameter|[string](../types/string.md) | Yes|Hidden parameter sent to bot for deep linking (https: api.telegram.org/bots#deep-linking)|


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

$Message = $MadelineProto->sendBotStartMessage(['bot_user_id' => int, 'chat_id' => InputPeer, 'parameter' => 'string', ]);
```

Or, if you're using the [PWRTelegram HTTP API](https://pwrtelegram.xyz):

### As a bot:

POST/GET to `https://api.pwrtelegram.xyz/botTOKEN/madeline`

Parameters:

* method - sendBotStartMessage
* params - `{"bot_user_id": int, "chat_id": InputPeer, "parameter": "string", }`



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/sendBotStartMessage`

Parameters:

bot_user_id - Json encoded int

chat_id - Json encoded InputPeer

parameter - Json encoded string




Or, if you're into Lua:

```
Message = sendBotStartMessage({bot_user_id=int, chat_id=InputPeer, parameter='string', })
```

