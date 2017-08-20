---
title: searchChats
description: Searches for specified query in the title and username of known chats, offline request. Returns chats in the order of them in the chat list
---
## Method: searchChats  
[Back to methods index](index.md)


YOU CANNOT USE THIS METHOD IN MADELINEPROTO


Searches for specified query in the title and username of known chats, offline request. Returns chats in the order of them in the chat list

### Params:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|query|[string](../types/string.md) | Yes|Query to search for, if query is empty, returns up to 20 recently found chats|
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

$Chats = $MadelineProto->searchChats(['query' => 'string', 'limit' => int, ]);
```

Or, if you're using the [PWRTelegram HTTP API](https://pwrtelegram.xyz):

### As a bot:

POST/GET to `https://api.pwrtelegram.xyz/botTOKEN/madeline`

Parameters:

* method - searchChats
* params - `{"query": "string", "limit": int, }`



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/searchChats`

Parameters:

query - Json encoded string

limit - Json encoded int




Or, if you're into Lua:

```
Chats = searchChats({query='string', limit=int, })
```

