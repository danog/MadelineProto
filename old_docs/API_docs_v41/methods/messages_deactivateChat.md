---
title: messages.deactivateChat
description: messages.deactivateChat parameters, return type and example
---
## Method: messages.deactivateChat  
[Back to methods index](index.md)


### Parameters:

| Name     |    Type       | Required |
|----------|---------------|----------|
|chat\_id|[InputPeer](../types/InputPeer.md) | Yes|
|enabled|[Bool](../types/Bool.md) | Yes|


### Return type: [Updates](../types/Updates.md)

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

$Updates = $MadelineProto->messages->deactivateChat(['chat_id' => InputPeer, 'enabled' => Bool, ]);
```

Or, if you're using the [PWRTelegram HTTP API](https://pwrtelegram.xyz):

### As a bot:

POST/GET to `https://api.pwrtelegram.xyz/botTOKEN/madeline`

Parameters:

* method - messages.deactivateChat
* params - `{"chat_id": InputPeer, "enabled": Bool, }`



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/messages.deactivateChat`

Parameters:

chat_id - Json encoded InputPeer

enabled - Json encoded Bool




Or, if you're into Lua:

```
Updates = messages.deactivateChat({chat_id=InputPeer, enabled=Bool, })
```

