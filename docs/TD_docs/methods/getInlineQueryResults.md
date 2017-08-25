---
title: getInlineQueryResults
description: Sends inline query to a bot and returns its results. Returns error with code 502 if bot fails to answer the query before query timeout expires. Unavailable for bots
---
## Method: getInlineQueryResults  
[Back to methods index](index.md)


YOU CANNOT USE THIS METHOD IN MADELINEPROTO


Sends inline query to a bot and returns its results. Returns error with code 502 if bot fails to answer the query before query timeout expires. Unavailable for bots

### Params:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|bot\_user\_id|[int](../types/int.md) | Yes|Identifier of the bot send query to|
|chat\_id|[InputPeer](../types/InputPeer.md) | Yes|Identifier of the chat, where the query is sent|
|user\_location|[location](../types/location.md) | Yes|User location, only if needed|
|query|[string](../types/string.md) | Yes|Text of the query|
|offset|[string](../types/string.md) | Yes|Offset of the first entry to return|


### Return type: [InlineQueryResults](../types/InlineQueryResults.md)

