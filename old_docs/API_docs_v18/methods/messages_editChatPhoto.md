---
title: messages.editChatPhoto
description: messages.editChatPhoto parameters, return type and example
---
## Method: messages.editChatPhoto  
[Back to methods index](index.md)


### Parameters:

| Name     |    Type       | Required |
|----------|---------------|----------|
|chat\_id|[InputPeer](../types/InputPeer.md) | Yes|
|photo|[InputChatPhoto](../types/InputChatPhoto.md) | Yes|


### Return type: [messages\_StatedMessage](../types/messages_StatedMessage.md)

### Can bots use this method: **YES**


### Errors this method can return:

| Error    | Description   |
|----------|---------------|
|CHAT_ID_INVALID|The provided chat id is invalid|
|PEER_ID_INVALID|The provided peer id is invalid|


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

$messages_StatedMessage = $MadelineProto->messages->editChatPhoto(['chat_id' => InputPeer, 'photo' => InputChatPhoto, ]);
```

Or, if you're using the [PWRTelegram HTTP API](https://pwrtelegram.xyz):

### As a bot:

POST/GET to `https://api.pwrtelegram.xyz/botTOKEN/madeline`

Parameters:

* method - messages.editChatPhoto
* params - `{"chat_id": InputPeer, "photo": InputChatPhoto, }`



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/messages.editChatPhoto`

Parameters:

chat_id - Json encoded InputPeer

photo - Json encoded InputChatPhoto




Or, if you're into Lua:

```
messages_StatedMessage = messages.editChatPhoto({chat_id=InputPeer, photo=InputChatPhoto, })
```

