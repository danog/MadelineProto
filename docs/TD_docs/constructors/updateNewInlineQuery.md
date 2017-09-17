---
title: updateNewInlineQuery
description: Bots only. New incoming inline query
---
## Constructor: updateNewInlineQuery  
[Back to constructors index](index.md)



Bots only. New incoming inline query

### Attributes:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|id|[long](../types/long.md) | Yes|Unique query identifier|
|sender\_user\_id|[int](../types/int.md) | Yes|Identifier of the user who sent the query|
|user\_location|[location](../types/location.md) | Yes|User location, provided by the client, nullable|
|query|[string](../types/string.md) | Yes|Text of the query|
|offset|[string](../types/string.md) | Yes|Offset of the first entry to return|



### Type: [Update](../types/Update.md)


