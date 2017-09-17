---
title: messages.editChatTitle
description: messages.editChatTitle parameters, return type and example
---
## Method: messages.editChatTitle  
[Back to methods index](index.md)


### Parameters:

| Name     |    Type       | Required |
|----------|---------------|----------|
|chat\_id|[InputPeer](../types/InputPeer.md) | Yes|
|title|[string](../types/string.md) | Yes|


### Return type: [messages\_StatedMessage](../types/messages_StatedMessage.md)

### Can bots use this method: **YES**


### Errors this method can return:

| Error    | Description   |
|----------|---------------|
|NEED_CHAT_INVALID|The provided chat is invalid|


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

$messages_StatedMessage = $MadelineProto->messages->editChatTitle(['chat_id' => InputPeer, 'title' => 'string', ]);
```

Or, if you're using the [PWRTelegram HTTP API](https://pwrtelegram.xyz):

### As a bot:

POST/GET to `https://api.pwrtelegram.xyz/botTOKEN/madeline`

Parameters:

* method - messages.editChatTitle
* params - `{"chat_id": InputPeer, "title": "string", }`



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/messages.editChatTitle`

Parameters:

chat_id - Json encoded InputPeer

title - Json encoded string




Or, if you're into Lua:

```
messages_StatedMessage = messages.editChatTitle({chat_id=InputPeer, title='string', })
```

