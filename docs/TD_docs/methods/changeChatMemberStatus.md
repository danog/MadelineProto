---
title: changeChatMemberStatus
description: Changes status of the chat member, need appropriate privileges. In channel chats, user will be added to chat members if he is yet not a member and there is less than 200 members in the channel. - Status will not be changed until chat state will be synchronized with the server. Status will not be changed if application is killed before it can send request to the server
---
## Method: changeChatMemberStatus  
[Back to methods index](index.md)


YOU CANNOT USE THIS METHOD IN MADELINEPROTO


Changes status of the chat member, need appropriate privileges. In channel chats, user will be added to chat members if he is yet not a member and there is less than 200 members in the channel. - Status will not be changed until chat state will be synchronized with the server. Status will not be changed if application is killed before it can send request to the server

### Params:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|chat\_id|[InputPeer](../types/InputPeer.md) | Yes|Chat identifier|
|user\_id|[int](../types/int.md) | Yes|Identifier of the user to edit status, bots can be editors in the channel chats|
|status|[ChatMemberStatus](../types/ChatMemberStatus.md) | Yes|New status of the member in the chat|


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

$Ok = $MadelineProto->changeChatMemberStatus(['chat_id' => InputPeer, 'user_id' => int, 'status' => ChatMemberStatus, ]);
```

Or, if you're using the [PWRTelegram HTTP API](https://pwrtelegram.xyz):

### As a bot:

POST/GET to `https://api.pwrtelegram.xyz/botTOKEN/madeline`

Parameters:

* method - changeChatMemberStatus
* params - `{"chat_id": InputPeer, "user_id": int, "status": ChatMemberStatus, }`



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/changeChatMemberStatus`

Parameters:

chat_id - Json encoded InputPeer

user_id - Json encoded int

status - Json encoded ChatMemberStatus




Or, if you're into Lua:

```
Ok = changeChatMemberStatus({chat_id=InputPeer, user_id=int, status=ChatMemberStatus, })
```

