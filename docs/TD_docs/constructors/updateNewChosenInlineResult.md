---
title: updateNewChosenInlineResult
description: Bots only. User has chosen a result of the inline query
---
## Constructor: updateNewChosenInlineResult  
[Back to constructors index](index.md)



Bots only. User has chosen a result of the inline query

### Attributes:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|sender\_user\_id|[int](../types/int.md) | Yes|Identifier of the user who sent the query|
|user\_location|[location](../types/location.md) | Yes|User location, provided by the client, nullable|
|query|[string](../types/string.md) | Yes|Text of the query|
|result\_id|[string](../types/string.md) | Yes|Identifier of the chosen result|
|inline\_message\_id|[string](../types/string.md) | Yes|Identifier of the sent inline message, if known|



### Type: [Update](../types/Update.md)


