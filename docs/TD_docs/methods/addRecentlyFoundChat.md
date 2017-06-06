---
title: addRecentlyFoundChat
description: Adds chat to the list of recently found chats. The chat is added to the beginning of the list. If the chat is already in the list, at first it is removed from the list
---
## Method: addRecentlyFoundChat  
[Back to methods index](index.md)


YOU CANNOT USE THIS METHOD IN MADELINEPROTO


Adds chat to the list of recently found chats. The chat is added to the beginning of the list. If the chat is already in the list, at first it is removed from the list

### Params:

| Name     |    Type       | Required | Description |
|----------|:-------------:|:--------:|------------:|
|chat\_id|[InputPeer](../types/InputPeer.md) | Yes|Identifier of the chat to add|


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

$Ok = $MadelineProto->addRecentlyFoundChat(['chat_id' => InputPeer, ]);
```

Or, if you're into Lua:

```
Ok = addRecentlyFoundChat({chat_id=InputPeer, })
```

