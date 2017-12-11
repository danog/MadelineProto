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
$MadelineProto->session = 'mySession.madeline';
if (isset($token)) { // Login as a bot
    $MadelineProto->bot_login($token);
}
if (isset($number)) { // Login as a user
    $MadelineProto->phone_login($number);
    $code = readline('Enter the code you received: '); // Or do this in two separate steps in an HTTP API
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

