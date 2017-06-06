---
title: getMessage
description: Returns information about a message
---
## Method: getMessage  
[Back to methods index](index.md)


YOU CANNOT USE THIS METHOD IN MADELINEPROTO


Returns information about a message

### Params:

| Name     |    Type       | Required | Description |
|----------|:-------------:|:--------:|------------:|
|chat\_id|[InputPeer](../types/InputPeer.md) | Yes|Identifier of the chat, message belongs to|
|message\_id|[long](../types/long.md) | Yes|Identifier of the message to get|


### Return type: [Message](../types/Message.md)

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

$Message = $MadelineProto->getMessage(['chat_id' => InputPeer, 'message_id' => long, ]);
```

Or, if you're into Lua:

```
Message = getMessage({chat_id=InputPeer, message_id=long, })
```

