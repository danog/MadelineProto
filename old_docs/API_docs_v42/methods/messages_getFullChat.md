---
title: messages.getFullChat
description: messages.getFullChat parameters, return type and example
---
## Method: messages.getFullChat  
[Back to methods index](index.md)


*You cannot use this method directly, use the get_pwr_chat, get_info, get_full_info methods instead (see https://daniil.it/MadelineProto for more info)*




### Parameters:

| Name     |    Type       | Required |
|----------|:-------------:|---------:|
|chat\_id|[InputPeer](../types/InputPeer.md) | Yes|


### Return type: [messages\_ChatFull](../types/messages_ChatFull.md)

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

$messages_ChatFull = $MadelineProto->messages->getFullChat(['chat_id' => InputPeer, ]);
```

Or, if you're using the [PWRTelegram HTTP API](https://pwrtelegram.xyz):

### As a bot:

POST/GET to `https://api.pwrtelegram.xyz/botTOKEN/madeline`

Parameters:

* method - messages.getFullChat
* params - `{"chat_id": InputPeer, }`



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/messages.getFullChat`

Parameters:

chat_id - Json encoded InputPeer



Or, if you're into Lua:

```
messages_ChatFull = messages.getFullChat({chat_id=InputPeer, })
```

