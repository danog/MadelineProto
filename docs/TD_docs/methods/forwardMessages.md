---
title: forwardMessages
description: Forwards previously sent messages. Returns forwarded messages in the same order as message identifiers passed in message_ids. If message can't be forwarded, null will be returned instead of the message. UpdateChatTopMessage will not be sent, so returned messages should be used to update chat top message
---
## Method: forwardMessages  
[Back to methods index](index.md)


YOU CANNOT USE THIS METHOD IN MADELINEPROTO


Forwards previously sent messages. Returns forwarded messages in the same order as message identifiers passed in message_ids. If message can't be forwarded, null will be returned instead of the message. UpdateChatTopMessage will not be sent, so returned messages should be used to update chat top message

### Params:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|chat\_id|[InputPeer](../types/InputPeer.md) | Yes|Identifier of a chat to forward messages|
|from\_chat\_id|[long](../types/long.md) | Yes|Identifier of a chat to forward from|
|message\_ids|Array of [long](../types/long.md) | Yes|Identifiers of messages to forward|
|disable\_notification|[Bool](../types/Bool.md) | Yes|Pass true, to disable notification about the message, doesn't works if messages are forwarded to secret chat|
|from\_background|[Bool](../types/Bool.md) | Yes|Pass true, if the message is sent from background|


### Return type: [Messages](../types/Messages.md)

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

$Messages = $MadelineProto->forwardMessages(['chat_id' => InputPeer, 'from_chat_id' => long, 'message_ids' => [long], 'disable_notification' => Bool, 'from_background' => Bool, ]);
```

Or, if you're using the [PWRTelegram HTTP API](https://pwrtelegram.xyz):

### As a bot:

POST/GET to `https://api.pwrtelegram.xyz/botTOKEN/madeline`

Parameters:

* method - forwardMessages
* params - `{"chat_id": InputPeer, "from_chat_id": long, "message_ids": [long], "disable_notification": Bool, "from_background": Bool, }`



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/forwardMessages`

Parameters:

chat_id - Json encoded InputPeer

from_chat_id - Json encoded long

message_ids - Json encoded  array of long

disable_notification - Json encoded Bool

from_background - Json encoded Bool




Or, if you're into Lua:

```
Messages = forwardMessages({chat_id=InputPeer, from_chat_id=long, message_ids={long}, disable_notification=Bool, from_background=Bool, })
```

