---
title: messages.setInlineBotResults
description: Bots only: set the results of an inline query
---
## Method: messages.setInlineBotResults  
[Back to methods index](index.md)


Bots only: set the results of an inline query

### Parameters:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|gallery|[Bool](../types/Bool.md) | Optional|Is this a gallery?|
|private|[Bool](../types/Bool.md) | Optional|Is this result private (not cached)?|
|query\_id|[long](../types/long.md) | Yes|Query ID|
|results|Array of [InputBotInlineResult](../types/InputBotInlineResult.md) | Yes|Results|
|cache\_time|[int](../types/int.md) | Yes|Cache time|
|next\_offset|[string](../types/string.md) | Optional|The next offset|
|switch\_pm|[InlineBotSwitchPM](../types/InlineBotSwitchPM.md) | Optional|Switch to PM?|


### Return type: [Bool](../types/Bool.md)

### Can bots use this method: **YES**


### MadelineProto Example:


```
if (!file_exists('madeline.php')) {
    copy('https://phar.madelineproto.xyz/madeline.php', 'madeline.php');
}
include 'madeline.php';

$MadelineProto = new \danog\MadelineProto\API('session.madeline');
$MadelineProto->start();

$Bool = $MadelineProto->messages->setInlineBotResults(['gallery' => Bool, 'private' => Bool, 'query_id' => long, 'results' => [InputBotInlineResult, InputBotInlineResult], 'cache_time' => int, 'next_offset' => 'string', 'switch_pm' => InlineBotSwitchPM, ]);
```

### [PWRTelegram HTTP API](https://pwrtelegram.xyz) example (NOT FOR MadelineProto):

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


