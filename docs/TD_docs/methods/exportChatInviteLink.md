---
title: exportChatInviteLink
description: Generates new chat invite link, previously generated link is revoked. Available for group and channel chats. Only creator of the chat can export chat invite link
---
## Method: exportChatInviteLink  
[Back to methods index](index.md)


YOU CANNOT USE THIS METHOD IN MADELINEPROTO


Generates new chat invite link, previously generated link is revoked. Available for group and channel chats. Only creator of the chat can export chat invite link

### Params:

| Name     |    Type       | Required | Description |
|----------|:-------------:|:--------:|------------:|
|chat\_id|[InputPeer](../types/InputPeer.md) | Yes|Chat identifier|


### Return type: [ChatInviteLink](../types/ChatInviteLink.md)

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

$ChatInviteLink = $MadelineProto->exportChatInviteLink(['chat_id' => InputPeer, ]);
```

Or, if you're into Lua:

```
ChatInviteLink = exportChatInviteLink({chat_id=InputPeer, })
```

