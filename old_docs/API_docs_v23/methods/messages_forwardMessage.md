---
title: messages.forwardMessage
description: messages.forwardMessage parameters, return type and example
---
## Method: messages.forwardMessage  
[Back to methods index](index.md)


### Parameters:

| Name     |    Type       | Required |
|----------|:-------------:|---------:|
|peer|[InputPeer](../types/InputPeer.md) | Yes|
|id|[int](../types/int.md) | Yes|


### Return type: [messages\_StatedMessage](../types/messages_StatedMessage.md)

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

$messages_StatedMessage = $MadelineProto->messages->forwardMessage(['peer' => InputPeer, 'id' => int, ]);
```

Or, if you're into Lua:

```
messages_StatedMessage = messages.forwardMessage({peer=InputPeer, id=int, })
```

