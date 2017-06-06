---
title: createChannelChat
description: Returns existing chat corresponding to the known channel
---
## Method: createChannelChat  
[Back to methods index](index.md)


YOU CANNOT USE THIS METHOD IN MADELINEPROTO


Returns existing chat corresponding to the known channel

### Params:

| Name     |    Type       | Required | Description |
|----------|:-------------:|:--------:|------------:|
|channel\_id|[int](../types/int.md) | Yes|Channel identifier|


### Return type: [Chat](../types/Chat.md)

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

$Chat = $MadelineProto->createChannelChat(['channel_id' => int, ]);
```

Or, if you're into Lua:

```
Chat = createChannelChat({channel_id=int, })
```

