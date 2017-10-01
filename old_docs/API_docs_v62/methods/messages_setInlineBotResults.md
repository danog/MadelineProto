---
title: messages.setInlineBotResults
description: messages.setInlineBotResults parameters, return type and example
---
## Method: messages.setInlineBotResults  
[Back to methods index](index.md)


### Parameters:

| Name     |    Type       | Required |
|----------|---------------|----------|
|gallery|[Bool](../types/Bool.md) | Optional|
|private|[Bool](../types/Bool.md) | Optional|
|query\_id|[long](../types/long.md) | Yes|
|results|Array of [InputBotInlineResult](../types/InputBotInlineResult.md) | Yes|
|cache\_time|[int](../types/int.md) | Yes|
|next\_offset|[string](../types/string.md) | Optional|
|switch\_pm|[InlineBotSwitchPM](../types/InlineBotSwitchPM.md) | Optional|


### Return type: [Bool](../types/Bool.md)

### Can bots use this method: **YES**


### Errors this method can return:

| Error    | Description   |
|----------|---------------|
|ARTICLE_TITLE_EMPTY|The title of the article is empty|
|BUTTON_DATA_INVALID|The provided button data is invalid|
|BUTTON_TYPE_INVALID|The type of one of the buttons you provided is invalid|
|BUTTON_URL_INVALID|Button URL invalid|
|MESSAGE_EMPTY|The provided message is empty|
|QUERY_ID_INVALID|The query ID is invalid|
|REPLY_MARKUP_INVALID|The provided reply markup is invalid|
|RESULT_TYPE_INVALID|Result type invalid|
|SEND_MESSAGE_TYPE_INVALID|The message type is invalid|
|START_PARAM_INVALID|Start parameter invalid|
|USER_BOT_INVALID|This method can only be called by a bot|


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

$Bool = $MadelineProto->messages->setInlineBotResults(['gallery' => Bool, 'private' => Bool, 'query_id' => long, 'results' => [InputBotInlineResult], 'cache_time' => int, 'next_offset' => 'string', 'switch_pm' => InlineBotSwitchPM, ]);
```

Or, if you're using the [PWRTelegram HTTP API](https://pwrtelegram.xyz):

### As a bot:

POST/GET to `https://api.pwrtelegram.xyz/botTOKEN/madeline`

Parameters:

* method - messages.setInlineBotResults
* params - `{"gallery": Bool, "private": Bool, "query_id": long, "results": [InputBotInlineResult], "cache_time": int, "next_offset": "string", "switch_pm": InlineBotSwitchPM, }`



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/messages.setInlineBotResults`

Parameters:

gallery - Json encoded Bool

private - Json encoded Bool

query_id - Json encoded long

results - Json encoded  array of InputBotInlineResult

cache_time - Json encoded int

next_offset - Json encoded string

switch_pm - Json encoded InlineBotSwitchPM




Or, if you're into Lua:

```
Bool = messages.setInlineBotResults({gallery=Bool, private=Bool, query_id=long, results={InputBotInlineResult}, cache_time=int, next_offset='string', switch_pm=InlineBotSwitchPM, })
```

