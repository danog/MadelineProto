---
title: changeChatTitle
description: Changes chat title. Title can't be changed for private chats. Title will not change until change will be synchronized with the server. Title will not be changed if application is killed before it can send request to the server. - There will be update about change of the title on success. Otherwise error will be returned
---
## Method: changeChatTitle  
[Back to methods index](index.md)


YOU CANNOT USE THIS METHOD IN MADELINEPROTO


Changes chat title. Title can't be changed for private chats. Title will not change until change will be synchronized with the server. Title will not be changed if application is killed before it can send request to the server. - There will be update about change of the title on success. Otherwise error will be returned

### Params:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|chat\_id|[InputPeer](../types/InputPeer.md) | Yes|Chat identifier|
|title|[string](../types/string.md) | Yes|New title of a chat, 0-255 characters|


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

$Ok = $MadelineProto->changeChatTitle(['chat_id' => InputPeer, 'title' => 'string', ]);
```

Or, if you're using the [PWRTelegram HTTP API](https://pwrtelegram.xyz):

### As a bot:

POST/GET to `https://api.pwrtelegram.xyz/botTOKEN/madeline`

Parameters:

* method - changeChatTitle
* params - `{"chat_id": InputPeer, "title": "string", }`



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/changeChatTitle`

Parameters:

chat_id - Json encoded InputPeer

title - Json encoded string




Or, if you're into Lua:

```
Ok = changeChatTitle({chat_id=InputPeer, title='string', })
```

