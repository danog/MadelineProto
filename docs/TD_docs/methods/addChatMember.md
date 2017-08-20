---
title: addChatMember
description: Adds new member to chat. Members can't be added to private or secret chats. Member will not be added until chat state will be synchronized with the server. Member will not be added if application is killed before it can send request to the server
---
## Method: addChatMember  
[Back to methods index](index.md)


YOU CANNOT USE THIS METHOD IN MADELINEPROTO


Adds new member to chat. Members can't be added to private or secret chats. Member will not be added until chat state will be synchronized with the server. Member will not be added if application is killed before it can send request to the server

### Params:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|chat\_id|[InputPeer](../types/InputPeer.md) | Yes|Chat identifier|
|user\_id|[int](../types/int.md) | Yes|Identifier of the user to add|
|forward\_limit|[int](../types/int.md) | Yes|Number of previous messages from chat to forward to new member, ignored for channel chats|


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

$Ok = $MadelineProto->addChatMember(['chat_id' => InputPeer, 'user_id' => int, 'forward_limit' => int, ]);
```

Or, if you're using the [PWRTelegram HTTP API](https://pwrtelegram.xyz):

### As a bot:

POST/GET to `https://api.pwrtelegram.xyz/botTOKEN/madeline`

Parameters:

* method - addChatMember
* params - `{"chat_id": InputPeer, "user_id": int, "forward_limit": int, }`



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/addChatMember`

Parameters:

chat_id - Json encoded InputPeer

user_id - Json encoded int

forward_limit - Json encoded int




Or, if you're into Lua:

```
Ok = addChatMember({chat_id=InputPeer, user_id=int, forward_limit=int, })
```

