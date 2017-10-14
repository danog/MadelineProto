---
title: ping_delay_disconnect
description: ping_delay_disconnect parameters, return type and example
---
## Method: ping\_delay\_disconnect  
[Back to methods index](index.md)


### Parameters:

| Name     |    Type       | Required |
|----------|---------------|----------|
|ping\_id|[long](../types/long.md) | Yes|
|disconnect\_delay|[int](../types/int.md) | Yes|


### Return type: [Pong](../types/Pong.md)

### Can bots use this method: **YES**


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

$Pong = $MadelineProto->ping_delay_disconnect(['ping_id' => long, 'disconnect_delay' => int, ]);
```

Or, if you're using the [PWRTelegram HTTP API](https://pwrtelegram.xyz):

### As a bot:

POST/GET to `https://api.pwrtelegram.xyz/botTOKEN/madeline`

Parameters:

* method - ping_delay_disconnect
* params - `{"ping_id": long, "disconnect_delay": int, }`



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/ping_delay_disconnect`

Parameters:

ping_id - Json encoded long

disconnect_delay - Json encoded int




Or, if you're into Lua:

```
Pong = ping_delay_disconnect({ping_id=long, disconnect_delay=int, })
```

