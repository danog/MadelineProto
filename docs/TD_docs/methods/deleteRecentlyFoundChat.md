---
title: deleteRecentlyFoundChat
description: Deletes chat from the list of recently found chats
---
## Method: deleteRecentlyFoundChat  
[Back to methods index](index.md)


YOU CANNOT USE THIS METHOD IN MADELINEPROTO


Deletes chat from the list of recently found chats

### Params:

| Name     |    Type       | Required | Description |
|----------|:-------------:|:--------:|------------:|
|chat\_id|[InputPeer](../types/InputPeer.md) | Yes|Identifier of the chat to delete|


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

$Ok = $MadelineProto->deleteRecentlyFoundChat(['chat_id' => InputPeer, ]);
```

Or, if you're into Lua:

```
Ok = deleteRecentlyFoundChat({chat_id=InputPeer, })
```

