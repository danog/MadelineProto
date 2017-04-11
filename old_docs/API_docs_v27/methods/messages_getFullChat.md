---
title: messages.getFullChat
description: messages.getFullChat parameters, return type and example
---
## Method: messages.getFullChat  
[Back to methods index](index.md)


### Parameters:

| Name     |    Type       | Required |
|----------|:-------------:|---------:|
|chat\_id|[InputPeer](../types/InputPeer.md) | Yes|


### Return type: [messages\_ChatFull](../types/messages_ChatFull.md)

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

$messages_ChatFull = $MadelineProto->messages->getFullChat(['chat_id' => InputPeer, ]);
```

Or, if you're into Lua:

```
messages_ChatFull = messages.getFullChat({chat_id=InputPeer, })
```

