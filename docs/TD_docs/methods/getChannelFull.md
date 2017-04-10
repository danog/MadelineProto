---
title: getChannelFull
description: Returns full information about a channel by its identifier, cached for at most 1 minute
---
## Method: getChannelFull  
[Back to methods index](index.md)


Returns full information about a channel by its identifier, cached for at most 1 minute

### Params:

| Name     |    Type       | Required | Description |
|----------|:-------------:|:--------:|------------:|
|channel\_id|[int](../types/int.md) | Yes|Channel identifier|


### Return type: [ChannelFull](../types/ChannelFull.md)

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

$ChannelFull = $MadelineProto->getChannelFull(['channel_id' => int, ]);
```

Or, if you're into Lua:

```
ChannelFull = getChannelFull({channel_id=int, })
```

