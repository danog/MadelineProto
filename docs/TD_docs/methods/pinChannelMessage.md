---
title: pinChannelMessage
description: Pins a message in a supergroup channel chat. Needs editor privileges in the channel
---
## Method: pinChannelMessage  
[Back to methods index](index.md)


YOU CANNOT USE THIS METHOD IN MADELINEPROTO


Pins a message in a supergroup channel chat. Needs editor privileges in the channel

### Params:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|channel\_id|[int](../types/int.md) | Yes|Identifier of the channel|
|message\_id|[long](../types/long.md) | Yes|Identifier of the new pinned message|
|disable\_notification|[Bool](../types/Bool.md) | Yes|True, if there should be no notification about the pinned message|


### Return type: [Ok](../types/Ok.md)

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

$Ok = $MadelineProto->pinChannelMessage(['channel_id' => int, 'message_id' => long, 'disable_notification' => Bool, ]);
```

Or, if you're using the [PWRTelegram HTTP API](https://pwrtelegram.xyz):

### As a bot:

POST/GET to `https://api.pwrtelegram.xyz/botTOKEN/madeline`

Parameters:

* method - pinChannelMessage
* params - `{"channel_id": int, "message_id": long, "disable_notification": Bool, }`



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/pinChannelMessage`

Parameters:

channel_id - Json encoded int

message_id - Json encoded long

disable_notification - Json encoded Bool




Or, if you're into Lua:

```
Ok = pinChannelMessage({channel_id=int, message_id=long, disable_notification=Bool, })
```

