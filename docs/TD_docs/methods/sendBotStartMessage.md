---
title: sendBotStartMessage
description: Invites bot to a chat (if it is not in the chat) and send /start to it. Bot can't be invited to a private chat other than chat with the bot. Bots can't be invited to broadcast channel chats and secret chats. Returns sent message. UpdateChatTopMessage will not be sent, so returned message should be used to update chat top message
---
## Method: sendBotStartMessage  
[Back to methods index](index.md)


Invites bot to a chat (if it is not in the chat) and send /start to it. Bot can't be invited to a private chat other than chat with the bot. Bots can't be invited to broadcast channel chats and secret chats. Returns sent message. UpdateChatTopMessage will not be sent, so returned message should be used to update chat top message

### Params:

| Name     |    Type       | Required | Description |
|----------|:-------------:|:--------:|------------:|
|bot\_user\_id|[int](../types/int.md) | Yes|Identifier of the bot|
|chat\_id|[long](../types/long.md) | Yes|Identifier of the chat|
|parameter|[string](../types/string.md) | Yes|Hidden parameter sent to bot for deep linking (https: api.telegram.org/bots#deep-linking)|


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

$Message = $MadelineProto->sendBotStartMessage(['bot_user_id' => int, 'chat_id' => long, 'parameter' => string, ]);
```

Or, if you're into Lua:

```
Message = sendBotStartMessage({bot_user_id=int, chat_id=long, parameter=string, })
```

