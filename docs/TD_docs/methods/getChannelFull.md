---
title: getChannelFull
description: Returns full information about a channel by its identifier, cached for at most 1 minute
---
## Method: getChannelFull  
[Back to methods index](index.md)


YOU CANNOT USE THIS METHOD IN MADELINEPROTO


Returns full information about a channel by its identifier, cached for at most 1 minute

### Params:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|channel\_id|[int](../types/int.md) | Yes|Channel identifier|


### Return type: [ChannelFull](../types/ChannelFull.md)

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

$ChannelFull = $MadelineProto->getChannelFull(['channel_id' => int, ]);
```

Or, if you're using the [PWRTelegram HTTP API](https://pwrtelegram.xyz):

### As a bot:

POST/GET to `https://api.pwrtelegram.xyz/botTOKEN/madeline`

Parameters:

* method - getChannelFull
* params - `{"channel_id": int, }`



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/getChannelFull`

Parameters:

channel_id - Json encoded int




Or, if you're into Lua:

```
ChannelFull = getChannelFull({channel_id=int, })
```

