---
title: inlineQueryResults
description: Represents results of the inline query. Use sendInlineQueryResultMessage to send the result of the query
---
## Constructor: inlineQueryResults  
[Back to constructors index](index.md)



Represents results of the inline query. Use sendInlineQueryResultMessage to send the result of the query

### Attributes:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|inline\_query\_id|[long](../types/long.md) | Yes|Unique identifier of the inline query|
|next\_offset|[string](../types/string.md) | Yes|Offset for the next request. If it is empty, there is no more results|
|results|Array of [InlineQueryResult](../constructors/InlineQueryResult.md) | Yes|Results of the query|
|switch\_pm\_text|[string](../types/string.md) | Yes|If non-empty, this text should be shown on the button, which opens private chat with the bot and sends bot start message with parameter switch_pm_parameter|
|switch\_pm\_parameter|[string](../types/string.md) | Yes|Parameter for the bot start message|



### Type: [InlineQueryResults](../types/InlineQueryResults.md)


### Example:

```
$inlineQueryResults = ['_' => 'inlineQueryResults', 'inline_query_id' => long, 'next_offset' => 'string', 'results' => [InlineQueryResult], 'switch_pm_text' => 'string', 'switch_pm_parameter' => 'string'];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "inlineQueryResults", "inline_query_id": long, "next_offset": "string", "results": [InlineQueryResult], "switch_pm_text": "string", "switch_pm_parameter": "string"}
```


Or, if you're into Lua:  


```
inlineQueryResults={_='inlineQueryResults', inline_query_id=long, next_offset='string', results={InlineQueryResult}, switch_pm_text='string', switch_pm_parameter='string'}

```


