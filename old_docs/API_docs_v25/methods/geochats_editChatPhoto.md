---
title: geochats.editChatPhoto
description: geochats.editChatPhoto parameters, return type and example
---
## Method: geochats.editChatPhoto  
[Back to methods index](index.md)


### Parameters:

| Name     |    Type       | Required |
|----------|---------------|----------|
|peer|[InputGeoChat](../types/InputGeoChat.md) | Yes|
|photo|[InputChatPhoto](../types/InputChatPhoto.md) | Yes|


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

$geochats_StatedMessage = $MadelineProto->geochats->editChatPhoto(['peer' => InputGeoChat, 'photo' => InputChatPhoto, ]);
```

Or, if you're using the [PWRTelegram HTTP API](https://pwrtelegram.xyz):

### As a bot:

POST/GET to `https://api.pwrtelegram.xyz/botTOKEN/madeline`

Parameters:

* method - geochats.editChatPhoto
* params - `{"peer": InputGeoChat, "photo": InputChatPhoto, }`



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/geochats.editChatPhoto`

Parameters:

peer - Json encoded InputGeoChat

photo - Json encoded InputChatPhoto




Or, if you're into Lua:

```
geochats_StatedMessage = geochats.editChatPhoto({peer=InputGeoChat, photo=InputChatPhoto, })
```

