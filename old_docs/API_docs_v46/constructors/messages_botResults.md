---
title: messages.botResults
description: messages_botResults attributes, type and example
---
## Constructor: messages.botResults  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|---------------|----------|
|gallery|[Bool](../types/Bool.md) | Optional|
|query\_id|[long](../types/long.md) | Yes|
|next\_offset|[string](../types/string.md) | Optional|
|results|Array of [BotInlineResult](../types/BotInlineResult.md) | Yes|



### Type: [messages\_BotResults](../types/messages_BotResults.md)


### Example:

```
$messages_botResults = ['_' => 'messages.botResults', 'gallery' => Bool, 'query_id' => long, 'next_offset' => 'string', 'results' => [BotInlineResult]];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "messages.botResults", "gallery": Bool, "query_id": long, "next_offset": "string", "results": [BotInlineResult]}
```


Or, if you're into Lua:  


```
messages_botResults={_='messages.botResults', gallery=Bool, query_id=long, next_offset='string', results={BotInlineResult}}

```


