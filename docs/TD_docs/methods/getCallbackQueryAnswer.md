---
title: getCallbackQueryAnswer
description: Sends callback query to a bot and returns answer to it. Unavailable for bots
---
## Method: getCallbackQueryAnswer  
[Back to methods index](index.md)


YOU CANNOT USE THIS METHOD IN MADELINEPROTO


Sends callback query to a bot and returns answer to it. Unavailable for bots

### Params:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|chat\_id|[InputPeer](../types/InputPeer.md) | Yes|Identifier of the chat with a message|
|message\_id|[long](../types/long.md) | Yes|Identifier of the message, from which the query is originated|
|payload|[CallbackQueryPayload](../types/CallbackQueryPayload.md) | Yes|Query payload|


### Return type: [CallbackQueryAnswer](../types/CallbackQueryAnswer.md)

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

$CallbackQueryAnswer = $MadelineProto->getCallbackQueryAnswer(['chat_id' => InputPeer, 'message_id' => long, 'payload' => CallbackQueryPayload, ]);
```

Or, if you're using the [PWRTelegram HTTP API](https://pwrtelegram.xyz):

### As a bot:

POST/GET to `https://api.pwrtelegram.xyz/botTOKEN/madeline`

Parameters:

* method - getCallbackQueryAnswer
* params - `{"chat_id": InputPeer, "message_id": long, "payload": CallbackQueryPayload, }`



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/getCallbackQueryAnswer`

Parameters:

chat_id - Json encoded InputPeer

message_id - Json encoded long

payload - Json encoded CallbackQueryPayload




Or, if you're into Lua:

```
CallbackQueryAnswer = getCallbackQueryAnswer({chat_id=InputPeer, message_id=long, payload=CallbackQueryPayload, })
```

