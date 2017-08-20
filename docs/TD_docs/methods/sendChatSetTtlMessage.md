---
title: sendChatSetTtlMessage
description: Changes current ttl setting in a secret chat and sends corresponding message
---
## Method: sendChatSetTtlMessage  
[Back to methods index](index.md)


YOU CANNOT USE THIS METHOD IN MADELINEPROTO


Changes current ttl setting in a secret chat and sends corresponding message

### Params:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|chat\_id|[InputPeer](../types/InputPeer.md) | Yes|Chat identifier|
|ttl|[int](../types/int.md) | Yes|New value of ttl in seconds|


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

$Message = $MadelineProto->sendChatSetTtlMessage(['chat_id' => InputPeer, 'ttl' => int, ]);
```

Or, if you're using the [PWRTelegram HTTP API](https://pwrtelegram.xyz):

### As a bot:

POST/GET to `https://api.pwrtelegram.xyz/botTOKEN/madeline`

Parameters:

* method - sendChatSetTtlMessage
* params - `{"chat_id": InputPeer, "ttl": int, }`



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/sendChatSetTtlMessage`

Parameters:

chat_id - Json encoded InputPeer

ttl - Json encoded int




Or, if you're into Lua:

```
Message = sendChatSetTtlMessage({chat_id=InputPeer, ttl=int, })
```

