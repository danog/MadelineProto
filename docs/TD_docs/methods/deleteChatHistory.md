---
title: deleteChatHistory
description: Deletes all messages in the chat. Can't be used for channel chats
---
## Method: deleteChatHistory  
[Back to methods index](index.md)


YOU CANNOT USE THIS METHOD IN MADELINEPROTO


Deletes all messages in the chat. Can't be used for channel chats

### Params:

| Name     |    Type       | Required | Description |
|----------|:-------------:|:--------:|------------:|
|chat\_id|[InputPeer](../types/InputPeer.md) | Yes|Chat identifier|
|remove\_from\_chat\_list|[Bool](../types/Bool.md) | Yes|Pass true, if chat should be removed from the chat list|


### Return type: [Ok](../types/Ok.md)

### Example:


```
$MadelineProto = new \danog\MadelineProto\API();
if (isset($token)) { // Login as a bot
    $this->bot_login($token);
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

$Ok = $MadelineProto->deleteChatHistory(['chat_id' => InputPeer, 'remove_from_chat_list' => Bool, ]);
```

Or, if you're into Lua:

```
Ok = deleteChatHistory({chat_id=InputPeer, remove_from_chat_list=Bool, })
```

