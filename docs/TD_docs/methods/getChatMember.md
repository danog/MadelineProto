---
title: getChatMember
description: Returns information about one participant of the chat
---
## Method: getChatMember  
[Back to methods index](index.md)


YOU CANNOT USE THIS METHOD IN MADELINEPROTO


Returns information about one participant of the chat

### Params:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|chat\_id|[InputPeer](../types/InputPeer.md) | Yes|Chat identifier|
|user\_id|[int](../types/int.md) | Yes|User identifier|


### Return type: [ChatMember](../types/ChatMember.md)

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

$ChatMember = $MadelineProto->getChatMember(['chat_id' => InputPeer, 'user_id' => int, ]);
```

Or, if you're using the [PWRTelegram HTTP API](https://pwrtelegram.xyz):

### As a bot:

POST/GET to `https://api.pwrtelegram.xyz/botTOKEN/madeline`

Parameters:

* method - getChatMember
* params - `{"chat_id": InputPeer, "user_id": int, }`



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/getChatMember`

Parameters:

chat_id - Json encoded InputPeer

user_id - Json encoded int




Or, if you're into Lua:

```
ChatMember = getChatMember({chat_id=InputPeer, user_id=int, })
```

