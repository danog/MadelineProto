---
title: messages.exportChatInvite
description: messages.exportChatInvite parameters, return type and example
---
## Method: messages.exportChatInvite  
[Back to methods index](index.md)


### Parameters:

| Name     |    Type       | Required |
|----------|---------------|----------|
|chat\_id|[InputPeer](../types/InputPeer.md) | Yes|


### Return type: [ExportedChatInvite](../types/ExportedChatInvite.md)

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

$ExportedChatInvite = $MadelineProto->messages->exportChatInvite(['chat_id' => InputPeer, ]);
```

Or, if you're using the [PWRTelegram HTTP API](https://pwrtelegram.xyz):

### As a bot:

POST/GET to `https://api.pwrtelegram.xyz/botTOKEN/madeline`

Parameters:

* method - messages.exportChatInvite
* params - `{"chat_id": InputPeer, }`



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/messages.exportChatInvite`

Parameters:

chat_id - Json encoded InputPeer




Or, if you're into Lua:

```
ExportedChatInvite = messages.exportChatInvite({chat_id=InputPeer, })
```

