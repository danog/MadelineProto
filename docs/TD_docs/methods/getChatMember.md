---
title: getChatMember
description: Returns information about one participant of the chat
---
## Method: getChatMember  
[Back to methods index](index.md)


Returns information about one participant of the chat

### Params:

| Name     |    Type       | Required | Description |
|----------|:-------------:|:--------:|------------:|
|chat\_id|[long](../types/long.md) | Yes|Chat identifier|
|user\_id|[int](../types/int.md) | Yes|User identifier|


### Return type: [ChatMember](../types/ChatMember.md)

### Example:


```
$MadelineProto = new \danog\MadelineProto\API();
if (isset($token)) {
    $this->bot_login($token);
}
if (isset($number)) {
    $sentCode = $MadelineProto->phone_login($number);
    echo 'Enter the code you received: ';
    $code = '';
    for ($x = 0; $x < $sentCode['type']['length']; $x++) {
        $code .= fgetc(STDIN);
    }
    $MadelineProto->complete_phone_login($code);
}

$ChatMember = $MadelineProto->getChatMember(['chat_id' => long, 'user_id' => int, ]);
```

Or, if you're into Lua:

```
ChatMember = getChatMember({chat_id=long, user_id=int, })
```

