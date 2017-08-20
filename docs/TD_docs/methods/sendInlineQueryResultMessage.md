---
title: sendInlineQueryResultMessage
description: Sends result of the inline query as a message. Returns sent message. UpdateChatTopMessage will not be sent, so returned message should be used to update chat top message. Always clears chat draft message
---
## Method: sendInlineQueryResultMessage  
[Back to methods index](index.md)


YOU CANNOT USE THIS METHOD IN MADELINEPROTO


Sends result of the inline query as a message. Returns sent message. UpdateChatTopMessage will not be sent, so returned message should be used to update chat top message. Always clears chat draft message

### Params:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|chat\_id|[InputPeer](../types/InputPeer.md) | Yes|Chat to send message|
|reply\_to\_message\_id|[long](../types/long.md) | Yes|Identifier of a message to reply to or 0|
|disable\_notification|[Bool](../types/Bool.md) | Yes|Pass true, to disable notification about the message, doesn't works in secret chats|
|from\_background|[Bool](../types/Bool.md) | Yes|Pass true, if the message is sent from background|
|query\_id|[long](../types/long.md) | Yes|Identifier of the inline query|
|result\_id|[string](../types/string.md) | Yes|Identifier of the inline result|


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

$Message = $MadelineProto->sendInlineQueryResultMessage(['chat_id' => InputPeer, 'reply_to_message_id' => long, 'disable_notification' => Bool, 'from_background' => Bool, 'query_id' => long, 'result_id' => 'string', ]);
```

Or, if you're using the [PWRTelegram HTTP API](https://pwrtelegram.xyz):

### As a bot:

POST/GET to `https://api.pwrtelegram.xyz/botTOKEN/madeline`

Parameters:

* method - sendInlineQueryResultMessage
* params - `{"chat_id": InputPeer, "reply_to_message_id": long, "disable_notification": Bool, "from_background": Bool, "query_id": long, "result_id": "string", }`



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/sendInlineQueryResultMessage`

Parameters:

chat_id - Json encoded InputPeer

reply_to_message_id - Json encoded long

disable_notification - Json encoded Bool

from_background - Json encoded Bool

query_id - Json encoded long

result_id - Json encoded string




Or, if you're into Lua:

```
Message = sendInlineQueryResultMessage({chat_id=InputPeer, reply_to_message_id=long, disable_notification=Bool, from_background=Bool, query_id=long, result_id='string', })
```

