---
title: createNewChannelChat
description: Creates new channel chat and send corresponding messageChannelChatCreate, returns created chat
---
## Method: createNewChannelChat  
[Back to methods index](index.md)


YOU CANNOT USE THIS METHOD IN MADELINEPROTO


Creates new channel chat and send corresponding messageChannelChatCreate, returns created chat

### Params:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|title|[string](../types/string.md) | Yes|Title of new channel chat, 0-255 characters|
|is\_supergroup|[Bool](../types/Bool.md) | Yes|True, if supergroup chat should be created|
|about|[string](../types/string.md) | Yes|Information about the channel, 0-255 characters|


### Return type: [Chat](../types/Chat.md)

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

$Chat = $MadelineProto->createNewChannelChat(['title' => 'string', 'is_supergroup' => Bool, 'about' => 'string', ]);
```

Or, if you're using the [PWRTelegram HTTP API](https://pwrtelegram.xyz):

### As a bot:

POST/GET to `https://api.pwrtelegram.xyz/botTOKEN/madeline`

Parameters:

* method - createNewChannelChat
* params - `{"title": "string", "is_supergroup": Bool, "about": "string", }`



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/createNewChannelChat`

Parameters:

title - Json encoded string

is_supergroup - Json encoded Bool

about - Json encoded string




Or, if you're into Lua:

```
Chat = createNewChannelChat({title='string', is_supergroup=Bool, about='string', })
```

