---
title: geochats.sendMessage
description: geochats.sendMessage parameters, return type and example
---
## Method: geochats.sendMessage  
[Back to methods index](index.md)


### Parameters:

| Name     |    Type       | Required |
|----------|---------------|----------|
|peer|[InputGeoChat](../types/InputGeoChat.md) | Yes|
|message|[string](../types/string.md) | Yes|


### Return type: [geochats\_StatedMessage](../types/geochats_StatedMessage.md)

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

$geochats_StatedMessage = $MadelineProto->geochats->sendMessage(['peer' => InputGeoChat, 'message' => 'string', ]);
```

Or, if you're using the [PWRTelegram HTTP API](https://pwrtelegram.xyz):

### As a bot:

POST/GET to `https://api.pwrtelegram.xyz/botTOKEN/madeline`

Parameters:

* method - geochats.sendMessage
* params - `{"peer": InputGeoChat, "message": "string", }`



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/geochats.sendMessage`

Parameters:

peer - Json encoded InputGeoChat

message - Json encoded string




Or, if you're into Lua:

```
geochats_StatedMessage = geochats.sendMessage({peer=InputGeoChat, message='string', })
```


## Return value 

If the length of the provided message is bigger than 4096, the message will be split in chunks and the method will be called multiple times, with the same parameters (except for the message), and an array of [geochats\_StatedMessage](../types/geochats_StatedMessage.md) will be returned instead.


