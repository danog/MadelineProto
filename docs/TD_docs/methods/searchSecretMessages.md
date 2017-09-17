---
title: searchSecretMessages
description: Searches for messages in secret chats. Returns result in reverse chronological order
---
## Method: searchSecretMessages  
[Back to methods index](index.md)


YOU CANNOT USE THIS METHOD IN MADELINEPROTO


Searches for messages in secret chats. Returns result in reverse chronological order

### Params:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|chat\_id|[InputPeer](../types/InputPeer.md) | Yes|Identifier of a chat to search in. Specify 0 to search in all secret chats|
|query|[string](../types/string.md) | Yes|Query to search for. If empty, searchChatMessages should be used instead|
|from\_search\_id|[long](../types/long.md) | Yes|Identifier from the result of previous request, use 0 to get results from the beginning|
|limit|[int](../types/int.md) | Yes|Maximum number of messages to be returned, can't be greater than 100|
|filter|[SearchMessagesFilter](../types/SearchMessagesFilter.md) | Yes|Filter for content of searched messages|


### Return type: [FoundMessages](../types/FoundMessages.md)

