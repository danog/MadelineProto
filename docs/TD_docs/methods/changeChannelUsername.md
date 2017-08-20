---
title: changeChannelUsername
description: Changes username of the channel. Needs creator privileges in the channel
---
## Method: changeChannelUsername  
[Back to methods index](index.md)


YOU CANNOT USE THIS METHOD IN MADELINEPROTO


Changes username of the channel. Needs creator privileges in the channel

### Params:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|channel\_id|[int](../types/int.md) | Yes|Identifier of the channel|
|username|[string](../types/string.md) | Yes|New value of username. Use empty string to remove username|


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

$Ok = $MadelineProto->changeChannelUsername(['channel_id' => int, 'username' => 'string', ]);
```

Or, if you're using the [PWRTelegram HTTP API](https://pwrtelegram.xyz):

### As a bot:

POST/GET to `https://api.pwrtelegram.xyz/botTOKEN/madeline`

Parameters:

* method - changeChannelUsername
* params - `{"channel_id": int, "username": "string", }`



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/changeChannelUsername`

Parameters:

channel_id - Json encoded int

username - Json encoded string




Or, if you're into Lua:

```
Ok = changeChannelUsername({channel_id=int, username='string', })
```

