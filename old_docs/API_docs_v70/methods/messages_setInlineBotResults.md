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

