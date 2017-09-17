---
title: getChatEventLog
description: Returns list of service actions taken by chat members and administrators in the last 48 hours, available only in channels. Requires administrator rights. Returns result in reverse chronological order, i. e. in order of decreasing event_id
---
## Method: getChatEventLog  
[Back to methods index](index.md)


YOU CANNOT USE THIS METHOD IN MADELINEPROTO


Returns list of service actions taken by chat members and administrators in the last 48 hours, available only in channels. Requires administrator rights. Returns result in reverse chronological order, i. e. in order of decreasing event_id

### Params:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|chat\_id|[InputPeer](../types/InputPeer.md) | Yes|Chat identifier|
|query|[string](../types/string.md) | Yes|Search query to filter events|
|from\_event\_id|[long](../types/long.md) | Yes|Identifier of an event from which to return result, you can use 0 to get results from the latest events|
|limit|[int](../types/int.md) | Yes|Maximum number of events to return, can't be greater than 100|
|filters|[chatEventLogFilters](../types/chatEventLogFilters.md) | Yes|Types of events to return, defaults to all|
|user\_ids|Array of [int](../types/int.md) | Yes|User identifiers, which events to return, defaults to all users|


### Return type: [ChatEvents](../types/ChatEvents.md)

