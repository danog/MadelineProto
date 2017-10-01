---
title: geochats.getFullChat
description: geochats.getFullChat parameters, return type and example
---
## Method: geochats.getFullChat  
[Back to methods index](index.md)


### Parameters:

| Name     |    Type       | Required |
|----------|---------------|----------|
|peer|[InputGeoChat](../types/InputGeoChat.md) | Yes|


### Return type: [messages\_ChatFull](../types/messages_ChatFull.md)

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

$messages_ChatFull = $MadelineProto->geochats->getFullChat(['peer' => InputGeoChat, ]);
```

Or, if you're using the [PWRTelegram HTTP API](https://pwrtelegram.xyz):

### As a bot:

POST/GET to `https://api.pwrtelegram.xyz/botTOKEN/madeline`

Parameters:

* method - geochats.getFullChat
* params - `{"peer": InputGeoChat, }`



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/geochats.getFullChat`

Parameters:

peer - Json encoded InputGeoChat




Or, if you're into Lua:

```
messages_ChatFull = geochats.getFullChat({peer=InputGeoChat, })
```

