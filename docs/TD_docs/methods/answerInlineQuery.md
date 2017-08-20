---
title: answerInlineQuery
description: Bots only. Sets result of the inline query
---
## Method: answerInlineQuery  
[Back to methods index](index.md)


YOU CANNOT USE THIS METHOD IN MADELINEPROTO


Bots only. Sets result of the inline query

### Params:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|inline\_query\_id|[long](../types/long.md) | Yes|Identifier of the inline query|
|is\_personal|[Bool](../types/Bool.md) | Yes|Does result of the query can be cached only for specified user|
|results|Array of [InputInlineQueryResult](../types/InputInlineQueryResult.md) | Yes|Results of the query|
|cache\_time|[int](../types/int.md) | Yes|Allowed time to cache results of the query in seconds|
|next\_offset|[string](../types/string.md) | Yes|Offset for the next inline query, pass empty string if there is no more results|
|switch\_pm\_text|[string](../types/string.md) | Yes|If non-empty, this text should be shown on the button, which opens private chat with the bot and sends bot start message with parameter switch_pm_parameter|
|switch\_pm\_parameter|[string](../types/string.md) | Yes|Parameter for the bot start message|


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

$Ok = $MadelineProto->answerInlineQuery(['inline_query_id' => long, 'is_personal' => Bool, 'results' => [InputInlineQueryResult], 'cache_time' => int, 'next_offset' => 'string', 'switch_pm_text' => 'string', 'switch_pm_parameter' => 'string', ]);
```

Or, if you're using the [PWRTelegram HTTP API](https://pwrtelegram.xyz):

### As a bot:

POST/GET to `https://api.pwrtelegram.xyz/botTOKEN/madeline`

Parameters:

* method - answerInlineQuery
* params - `{"inline_query_id": long, "is_personal": Bool, "results": [InputInlineQueryResult], "cache_time": int, "next_offset": "string", "switch_pm_text": "string", "switch_pm_parameter": "string", }`



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/answerInlineQuery`

Parameters:

inline_query_id - Json encoded long

is_personal - Json encoded Bool

results - Json encoded  array of InputInlineQueryResult

cache_time - Json encoded int

next_offset - Json encoded string

switch_pm_text - Json encoded string

switch_pm_parameter - Json encoded string




Or, if you're into Lua:

```
Ok = answerInlineQuery({inline_query_id=long, is_personal=Bool, results={InputInlineQueryResult}, cache_time=int, next_offset='string', switch_pm_text='string', switch_pm_parameter='string', })
```

