---
title: messages.deleteChannelMessages
description: messages.deleteChannelMessages parameters, return type and example
---
## Method: messages.deleteChannelMessages  
[Back to methods index](index.md)


### Parameters:

| Name     |    Type       | Required |
|----------|---------------|----------|
|peer|[InputPeer](../types/InputPeer.md) | Yes|
|id|Array of [int](../types/int.md) | Yes|


### Return type: [messages\_AffectedMessages](../types/messages_AffectedMessages.md)

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

$messages_AffectedMessages = $MadelineProto->messages->deleteChannelMessages(['peer' => InputPeer, 'id' => [int], ]);
```

Or, if you're using the [PWRTelegram HTTP API](https://pwrtelegram.xyz):

### As a bot:

POST/GET to `https://api.pwrtelegram.xyz/botTOKEN/madeline`

Parameters:

* method - messages.deleteChannelMessages
* params - `{"peer": InputPeer, "id": [int], }`



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/messages.deleteChannelMessages`

Parameters:

peer - Json encoded InputPeer

id - Json encoded  array of int




Or, if you're into Lua:

```
messages_AffectedMessages = messages.deleteChannelMessages({peer=InputPeer, id={int}, })
```

