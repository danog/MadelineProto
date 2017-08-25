---
title: answerCallbackQuery
description: Bots only. Sets result of a callback query
---
## Method: answerCallbackQuery  
[Back to methods index](index.md)


YOU CANNOT USE THIS METHOD IN MADELINEPROTO


Bots only. Sets result of a callback query

### Params:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|callback\_query\_id|[long](../types/long.md) | Yes|Identifier of the callback query|
|text|[string](../types/string.md) | Yes|Text of the answer|
|show\_alert|[Bool](../types/Bool.md) | Yes|If true, an alert should be shown to the user instead of a toast|
|url|[string](../types/string.md) | Yes|Url to be opened|
|cache\_time|[int](../types/int.md) | Yes|Allowed time to cache result of the query in seconds|


### Return type: [Ok](../types/Ok.md)

