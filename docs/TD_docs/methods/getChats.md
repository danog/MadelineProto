---
title: getChats
description: Returns list of chats in the right order, chats are sorted by (order, chat_id) in decreasing order. For example, to get list of chats from the beginning, the offset_order should be equal 2^63 - 1
---
## Method: getChats  
[Back to methods index](index.md)


YOU CANNOT USE THIS METHOD IN MADELINEPROTO


Returns list of chats in the right order, chats are sorted by (order, chat_id) in decreasing order. For example, to get list of chats from the beginning, the offset_order should be equal 2^63 - 1

### Params:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|offset\_order|[long](../types/long.md) | Yes|Chat order to return chats from|
|offset\_chat\_id|[long](../types/long.md) | Yes|Chat identifier to return chats from|
|limit|[int](../types/int.md) | Yes|Maximum number of chats to be returned|


### Return type: [Chats](../types/Chats.md)

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

$Chats = $MadelineProto->getChats(['offset_order' => long, 'offset_chat_id' => long, 'limit' => int, ]);
```

Or, if you're using the [PWRTelegram HTTP API](https://pwrtelegram.xyz):

### As a bot:

POST/GET to `https://api.pwrtelegram.xyz/botTOKEN/madeline`

Parameters:

* method - getChats
* params - `{"offset_order": long, "offset_chat_id": long, "limit": int, }`



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/getChats`

Parameters:

offset_order - Json encoded long

offset_chat_id - Json encoded long

limit - Json encoded int




Or, if you're into Lua:

```
Chats = getChats({offset_order=long, offset_chat_id=long, limit=int, })
```

