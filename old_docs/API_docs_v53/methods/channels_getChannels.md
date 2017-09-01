---
title: channels.getChannels
description: channels.getChannels parameters, return type and example
---
## Method: channels.getChannels  
[Back to methods index](index.md)


### Parameters:

| Name     |    Type       | Required |
|----------|---------------|----------|
|id|Array of [InputChannel](../types/InputChannel.md) | Yes|


### Return type: [messages\_Chats](../types/messages_Chats.md)

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

$messages_Chats = $MadelineProto->channels->getChannels(['id' => [InputChannel], ]);
```

Or, if you're using the [PWRTelegram HTTP API](https://pwrtelegram.xyz):

### As a bot:

POST/GET to `https://api.pwrtelegram.xyz/botTOKEN/madeline`

Parameters:

* method - channels.getChannels
* params - `{"id": [InputChannel], }`



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/channels.getChannels`

Parameters:

id - Json encoded  array of InputChannel




Or, if you're into Lua:

```
messages_Chats = channels.getChannels({id={InputChannel}, })
```

