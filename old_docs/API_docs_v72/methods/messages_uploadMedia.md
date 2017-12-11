---
title: messages.uploadMedia
description: messages.uploadMedia parameters, return type and example
---
## Method: messages.uploadMedia  
[Back to methods index](index.md)


### Parameters:

| Name     |    Type       | Required |
|----------|---------------|----------|
|peer|[InputPeer](../types/InputPeer.md) | Yes|
|media|[InputMedia](../types/InputMedia.md) | Yes|


### Return type: [MessageMedia](../types/MessageMedia.md)

### Can bots use this method: **YES**


### Errors this method can return:

| Error    | Description   |
|----------|---------------|
|BOT_MISSING|This method can only be run by a bot|
|MEDIA_INVALID|Media invalid|
|PEER_ID_INVALID|The provided peer id is invalid|


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

$MessageMedia = $MadelineProto->messages->uploadMedia(['peer' => InputPeer, 'media' => InputMedia, ]);
```

Or, if you're using the [PWRTelegram HTTP API](https://pwrtelegram.xyz):

### As a bot:

POST/GET to `https://api.pwrtelegram.xyz/botTOKEN/madeline`

Parameters:

* method - messages.uploadMedia
* params - `{"peer": InputPeer, "media": InputMedia, }`



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/messages.uploadMedia`

Parameters:

peer - Json encoded InputPeer

media - Json encoded InputMedia




Or, if you're into Lua:

```
MessageMedia = messages.uploadMedia({peer=InputPeer, media=InputMedia, })
```

