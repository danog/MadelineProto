---
title: searchMessages
description: Searches for messages in all chats except secret chats. Returns result in reverse chronological order, i. e. in order of decreasing (date, chat_id, message_id)
---
## Method: searchMessages  
[Back to methods index](index.md)


YOU CANNOT USE THIS METHOD IN MADELINEPROTO


Searches for messages in all chats except secret chats. Returns result in reverse chronological order, i. e. in order of decreasing (date, chat_id, message_id)

### Params:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|query|[string](../types/string.md) | Yes|Query to search for|
|offset\_date|[int](../types/int.md) | Yes|Date of the message to search from, you can use 0 or any date in the future to get results from the beginning|
|offset\_chat\_id|[int53](../types/int53.md) | Yes|Chat identifier of the last found message or 0 for the first request|
|offset\_message\_id|[int53](../types/int53.md) | Yes|Message identifier of the last found message or 0 for the first request|
|limit|[int](../types/int.md) | Yes|Maximum number of messages to be returned, at most 100|


### Return type: [Messages](../types/Messages.md)

