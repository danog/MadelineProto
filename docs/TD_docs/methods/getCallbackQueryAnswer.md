---
title: getCallbackQueryAnswer
description: Sends callback query to a bot and returns answer to it. Unavailable for bots
---
## Method: getCallbackQueryAnswer  
[Back to methods index](index.md)


Sends callback query to a bot and returns answer to it. Unavailable for bots

### Params:

| Name     |    Type       | Required | Description |
|----------|:-------------:|:--------:|------------:|
|chat\_id|[long](../types/long.md) | Yes|Identifier of the chat with a message|
|message\_id|[long](../types/long.md) | Yes|Identifier of the message, from which the query is originated|
|payload|[CallbackQueryPayload](../types/CallbackQueryPayload.md) | Yes|Query payload|


### Return type: [CallbackQueryAnswer](../types/CallbackQueryAnswer.md)

### Example:


```
$MadelineProto = new \danog\MadelineProto\API();
if (isset($token)) { // Login as a bot
    $this->bot_login($token);
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

$CallbackQueryAnswer = $MadelineProto->getCallbackQueryAnswer(['chat_id' => long, 'message_id' => long, 'payload' => CallbackQueryPayload, ]);
```

Or, if you're into Lua:

```
CallbackQueryAnswer = getCallbackQueryAnswer({chat_id=long, message_id=long, payload=CallbackQueryPayload, })
```

