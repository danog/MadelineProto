---
title: unpinChannelMessage
description: Removes pinned message in the supergroup channel. Needs editor privileges in the channel
---
## Method: unpinChannelMessage  
[Back to methods index](index.md)


YOU CANNOT USE THIS METHOD IN MADELINEPROTO


Removes pinned message in the supergroup channel. Needs editor privileges in the channel

### Params:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|channel\_id|[int](../types/int.md) | Yes|Identifier of the channel|


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

$Ok = $MadelineProto->unpinChannelMessage(['channel_id' => int, ]);
```

Or, if you're using the [PWRTelegram HTTP API](https://pwrtelegram.xyz):

### As a bot:

POST/GET to `https://api.pwrtelegram.xyz/botTOKEN/madeline`

Parameters:

* method - unpinChannelMessage
* params - `{"channel_id": int, }`



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/unpinChannelMessage`

Parameters:

channel_id - Json encoded int




Or, if you're into Lua:

```
Ok = unpinChannelMessage({channel_id=int, })
```

