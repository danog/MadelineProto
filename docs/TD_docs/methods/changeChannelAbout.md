---
title: changeChannelAbout
description: Changes information about the channel. Needs creator privileges in the broadcast channel or editor privileges in the supergroup channel
---
## Method: changeChannelAbout  
[Back to methods index](index.md)


YOU CANNOT USE THIS METHOD IN MADELINEPROTO


Changes information about the channel. Needs creator privileges in the broadcast channel or editor privileges in the supergroup channel

### Params:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|channel\_id|[int](../types/int.md) | Yes|Identifier of the channel|
|about|[string](../types/string.md) | Yes|New value of about, 0-255 characters|


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

$Ok = $MadelineProto->changeChannelAbout(['channel_id' => int, 'about' => 'string', ]);
```

Or, if you're using the [PWRTelegram HTTP API](https://pwrtelegram.xyz):

### As a bot:

POST/GET to `https://api.pwrtelegram.xyz/botTOKEN/madeline`

Parameters:

* method - changeChannelAbout
* params - `{"channel_id": int, "about": "string", }`



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/changeChannelAbout`

Parameters:

channel_id - Json encoded int

about - Json encoded string




Or, if you're into Lua:

```
Ok = changeChannelAbout({channel_id=int, about='string', })
```

