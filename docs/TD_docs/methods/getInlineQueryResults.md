---
title: getInlineQueryResults
description: Sends inline query to a bot and returns its results. Unavailable for bots
---
## Method: getInlineQueryResults  
[Back to methods index](index.md)


YOU CANNOT USE THIS METHOD IN MADELINEPROTO


Sends inline query to a bot and returns its results. Unavailable for bots

### Params:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|bot\_user\_id|[int](../types/int.md) | Yes|Identifier of the bot send query to|
|chat\_id|[InputPeer](../types/InputPeer.md) | Yes|Identifier of the chat, where the query is sent|
|user\_location|[location](../types/location.md) | Yes|User location, only if needed|
|query|[string](../types/string.md) | Yes|Text of the query|
|offset|[string](../types/string.md) | Yes|Offset of the first entry to return|


### Return type: [InlineQueryResults](../types/InlineQueryResults.md)

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

$InlineQueryResults = $MadelineProto->getInlineQueryResults(['bot_user_id' => int, 'chat_id' => InputPeer, 'user_location' => location, 'query' => 'string', 'offset' => 'string', ]);
```

Or, if you're using the [PWRTelegram HTTP API](https://pwrtelegram.xyz):

### As a bot:

POST/GET to `https://api.pwrtelegram.xyz/botTOKEN/madeline`

Parameters:

* method - getInlineQueryResults
* params - `{"bot_user_id": int, "chat_id": InputPeer, "user_location": location, "query": "string", "offset": "string", }`



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/getInlineQueryResults`

Parameters:

bot_user_id - Json encoded int

chat_id - Json encoded InputPeer

user_location - Json encoded location

query - Json encoded string

offset - Json encoded string




Or, if you're into Lua:

```
InlineQueryResults = getInlineQueryResults({bot_user_id=int, chat_id=InputPeer, user_location=location, query='string', offset='string', })
```

