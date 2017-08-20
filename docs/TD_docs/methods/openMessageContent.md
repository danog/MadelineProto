---
title: openMessageContent
description: Message content is opened, for example the user has opened a photo, a video, a document, a location or a venue or have listened to an audio or a voice message
---
## Method: openMessageContent  
[Back to methods index](index.md)


YOU CANNOT USE THIS METHOD IN MADELINEPROTO


Message content is opened, for example the user has opened a photo, a video, a document, a location or a venue or have listened to an audio or a voice message

### Params:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|chat\_id|[InputPeer](../types/InputPeer.md) | Yes|Chat identifier of the message|
|message\_id|[long](../types/long.md) | Yes|Identifier of the message with opened content|


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

$Ok = $MadelineProto->openMessageContent(['chat_id' => InputPeer, 'message_id' => long, ]);
```

Or, if you're using the [PWRTelegram HTTP API](https://pwrtelegram.xyz):

### As a bot:

POST/GET to `https://api.pwrtelegram.xyz/botTOKEN/madeline`

Parameters:

* method - openMessageContent
* params - `{"chat_id": InputPeer, "message_id": long, }`



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/openMessageContent`

Parameters:

chat_id - Json encoded InputPeer

message_id - Json encoded long




Or, if you're into Lua:

```
Ok = openMessageContent({chat_id=InputPeer, message_id=long, })
```

