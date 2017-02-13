---
title: messages.setInlineBotResults
description: messages.setInlineBotResults parameters, return type and example
---
## Method: messages.setInlineBotResults  
[Back to methods index](index.md)


### Parameters:

| Name     |    Type       | Required |
|----------|:-------------:|---------:|
|gallery|[Bool](../types/Bool.md) | Optional|
|private|[Bool](../types/Bool.md) | Optional|
|query\_id|[long](../types/long.md) | Required|
|results|Array of [InputBotInlineResult](../types/InputBotInlineResult.md) | Required|
|cache\_time|[int](../types/int.md) | Required|
|next\_offset|[string](../types/string.md) | Optional|


### Return type: [Bool](../types/Bool.md)

### Example:


```
$MadelineProto = new \danog\MadelineProto\API();
if (isset($token)) {
    $this->bot_login($token);
}
if (isset($number)) {
    $sentCode = $MadelineProto->phone_login($number);
    echo 'Enter the code you received: ';
    $code = '';
    for ($x = 0; $x < $sentCode['type']['length']; $x++) {
        $code .= fgetc(STDIN);
    }
    $MadelineProto->complete_phone_login($code);
}

$Bool = $MadelineProto->messages->setInlineBotResults(['gallery' => Bool, 'private' => Bool, 'query_id' => long, 'results' => [InputBotInlineResult], 'cache_time' => int, 'next_offset' => string, ]);
```
