---
title: searchChatMessages
description: Searches for messages with given words in the chat. Returns result in reverse chronological order, i. e. in order of decreasimg message_id. Doesn't work in secret chats
---
## Method: searchChatMessages  
[Back to methods index](index.md)


YOU CANNOT USE THIS METHOD IN MADELINEPROTO


Searches for messages with given words in the chat. Returns result in reverse chronological order, i. e. in order of decreasimg message_id. Doesn't work in secret chats

### Params:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|chat\_id|[InputPeer](../types/InputPeer.md) | Yes|Chat identifier to search in|
|query|[string](../types/string.md) | Yes|Query to search for|
|from\_message\_id|[long](../types/long.md) | Yes|Identifier of the message from which we need a history, you can use 0 to get results from beginning|
|limit|[int](../types/int.md) | Yes|Maximum number of messages to be returned, can't be greater than 100|
|filter|[SearchMessagesFilter](../types/SearchMessagesFilter.md) | Yes|Filter for content of searched messages|


### Return type: [Messages](../types/Messages.md)

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

$Messages = $MadelineProto->searchChatMessages(['chat_id' => InputPeer, 'query' => 'string', 'from_message_id' => long, 'limit' => int, 'filter' => SearchMessagesFilter, ]);
```

Or, if you're using the [PWRTelegram HTTP API](https://pwrtelegram.xyz):

### As a bot:

POST/GET to `https://api.pwrtelegram.xyz/botTOKEN/madeline`

Parameters:

* method - searchChatMessages
* params - `{"chat_id": InputPeer, "query": "string", "from_message_id": long, "limit": int, "filter": SearchMessagesFilter, }`



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/searchChatMessages`

Parameters:

chat_id - Json encoded InputPeer

query - Json encoded string

from_message_id - Json encoded long

limit - Json encoded int

filter - Json encoded SearchMessagesFilter




Or, if you're into Lua:

```
Messages = searchChatMessages({chat_id=InputPeer, query='string', from_message_id=long, limit=int, filter=SearchMessagesFilter, })
```

