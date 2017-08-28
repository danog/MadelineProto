---
title: channels.getImportantHistory
description: channels.getImportantHistory parameters, return type and example
---
## Method: channels.getImportantHistory  
[Back to methods index](index.md)


### Parameters:

| Name     |    Type       | Required |
|----------|---------------|----------|
|channel|[InputChannel](../types/InputChannel.md) | Yes|
|offset\_id|[int](../types/int.md) | Yes|
|add\_offset|[int](../types/int.md) | Yes|
|limit|[int](../types/int.md) | Yes|
|max\_id|[int](../types/int.md) | Yes|
|min\_id|[int](../types/int.md) | Yes|


### Return type: [messages\_Messages](../types/messages_Messages.md)

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

$messages_Messages = $MadelineProto->channels->getImportantHistory(['channel' => InputChannel, 'offset_id' => int, 'add_offset' => int, 'limit' => int, 'max_id' => int, 'min_id' => int, ]);
```

Or, if you're using the [PWRTelegram HTTP API](https://pwrtelegram.xyz):

### As a bot:

POST/GET to `https://api.pwrtelegram.xyz/botTOKEN/madeline`

Parameters:

* method - channels.getImportantHistory
* params - `{"channel": InputChannel, "offset_id": int, "add_offset": int, "limit": int, "max_id": int, "min_id": int, }`



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/channels.getImportantHistory`

Parameters:

channel - Json encoded InputChannel

offset_id - Json encoded int

add_offset - Json encoded int

limit - Json encoded int

max_id - Json encoded int

min_id - Json encoded int




Or, if you're into Lua:

```
messages_Messages = channels.getImportantHistory({channel=InputChannel, offset_id=int, add_offset=int, limit=int, max_id=int, min_id=int, })
```

