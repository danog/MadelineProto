---
title: searchMessages
description: Searches for messages in all chats except secret chats. Returns result in reverse chronological order, i. e. in order of decreasing (date, chat_id, message_id)
---
## Method: searchMessages  
[Back to methods index](index.md)


YOU CANNOT USE THIS METHOD IN MADELINEPROTO


Searches for messages in all chats except secret chats. Returns result in reverse chronological order, i. e. in order of decreasing (date, chat_id, message_id)

### Params:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|query|[string](../types/string.md) | Yes|Query to search for|
|offset\_date|[int](../types/int.md) | Yes|Date of the message to search from, you can use 0 or any date in the future to get results from the beginning|
|offset\_chat\_id|[long](../types/long.md) | Yes|Chat identifier of the last found message or 0 for the first request|
|offset\_message\_id|[long](../types/long.md) | Yes|Message identifier of the last found message or 0 for the first request|
|limit|[int](../types/int.md) | Yes|Maximum number of messages to be returned, can't be greater than 100|


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

$Messages = $MadelineProto->searchMessages(['query' => 'string', 'offset_date' => int, 'offset_chat_id' => long, 'offset_message_id' => long, 'limit' => int, ]);
```

Or, if you're using the [PWRTelegram HTTP API](https://pwrtelegram.xyz):

### As a bot:

POST/GET to `https://api.pwrtelegram.xyz/botTOKEN/madeline`

Parameters:

* method - searchMessages
* params - `{"query": "string", "offset_date": int, "offset_chat_id": long, "offset_message_id": long, "limit": int, }`



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/searchMessages`

Parameters:

query - Json encoded string

offset_date - Json encoded int

offset_chat_id - Json encoded long

offset_message_id - Json encoded long

limit - Json encoded int




Or, if you're into Lua:

```
Messages = searchMessages({query='string', offset_date=int, offset_chat_id=long, offset_message_id=long, limit=int, })
```

