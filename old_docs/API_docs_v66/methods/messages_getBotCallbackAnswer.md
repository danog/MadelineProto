---
title: messages.getBotCallbackAnswer
description: messages.getBotCallbackAnswer parameters, return type and example
---
## Method: messages.getBotCallbackAnswer  
[Back to methods index](index.md)


### Parameters:

| Name     |    Type       | Required |
|----------|:-------------:|---------:|
|game|[Bool](../types/Bool.md) | Optional|
|peer|[InputPeer](../types/InputPeer.md) | Yes|
|msg\_id|[int](../types/int.md) | Yes|
|data|[bytes](../types/bytes.md) | Optional|


### Return type: [messages\_BotCallbackAnswer](../types/messages_BotCallbackAnswer.md)

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

$messages_BotCallbackAnswer = $MadelineProto->messages->getBotCallbackAnswer(['game' => Bool, 'peer' => InputPeer, 'msg_id' => int, 'data' => bytes, ]);
```

Or, if you're into Lua:

```
messages_BotCallbackAnswer = messages.getBotCallbackAnswer({game=Bool, peer=InputPeer, msg_id=int, data=bytes, })
```

