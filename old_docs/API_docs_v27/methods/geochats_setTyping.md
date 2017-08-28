---
title: geochats.setTyping
description: geochats.setTyping parameters, return type and example
---
## Method: geochats.setTyping  
[Back to methods index](index.md)


### Parameters:

| Name     |    Type       | Required |
|----------|---------------|----------|
|peer|[InputGeoChat](../types/InputGeoChat.md) | Yes|
|typing|[Bool](../types/Bool.md) | Yes|


### Return type: [Bool](../types/Bool.md)

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

$Bool = $MadelineProto->geochats->setTyping(['peer' => InputGeoChat, 'typing' => Bool, ]);
```

Or, if you're using the [PWRTelegram HTTP API](https://pwrtelegram.xyz):

### As a bot:

POST/GET to `https://api.pwrtelegram.xyz/botTOKEN/madeline`

Parameters:

* method - geochats.setTyping
* params - `{"peer": InputGeoChat, "typing": Bool, }`



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/geochats.setTyping`

Parameters:

peer - Json encoded InputGeoChat

typing - Json encoded Bool




Or, if you're into Lua:

```
Bool = geochats.setTyping({peer=InputGeoChat, typing=Bool, })
```

