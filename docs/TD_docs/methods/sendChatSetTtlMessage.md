---
title: sendChatSetTtlMessage
description: Changes current ttl setting in a secret chat and sends corresponding message
---
## Method: sendChatSetTtlMessage  
[Back to methods index](index.md)


Changes current ttl setting in a secret chat and sends corresponding message

### Params:

| Name     |    Type       | Required | Description |
|----------|:-------------:|:--------:|------------:|
|chat\_id|[long](../types/long.md) | Yes|Chat identifier|
|ttl|[int](../types/int.md) | Yes|New value of ttl in seconds|


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

$Message = $MadelineProto->sendChatSetTtlMessage(['chat_id' => long, 'ttl' => int, ]);
```

Or, if you're into Lua:

```
Message = sendChatSetTtlMessage({chat_id=long, ttl=int, })
```

