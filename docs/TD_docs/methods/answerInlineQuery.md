---
title: answerInlineQuery
description: Bots only. Sets result of an inline query
---
## Method: answerInlineQuery  
[Back to methods index](index.md)


YOU CANNOT USE THIS METHOD IN MADELINEPROTO


Bots only. Sets result of an inline query

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

