---
title: searchChatMessages
description: Searches for messages with given words in the chat. Returns result in reverse chronological order, i. e. in order of decreasimg message_id. Doesn't work in secret chats
---
## Method: searchChatMessages  
[Back to methods index](index.md)


YOU CANNOT USE THIS METHOD IN MADELINEPROTO


Searches for messages with given words in the chat. Returns result in reverse chronological order, i. e. in order of decreasimg message_id. Doesn't work in secret chats

### Params:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|chat\_id|[InputPeer](../types/InputPeer.md) | Yes|Chat identifier to search in|
|query|[string](../types/string.md) | Yes|Query to search for|
|from\_message\_id|[long](../types/long.md) | Yes|Identifier of the message from which we need a history, you can use 0 to get results from beginning|
|limit|[int](../types/int.md) | Yes|Maximum number of messages to be returned, can't be greater than 100|
|filter|[SearchMessagesFilter](../types/SearchMessagesFilter.md) | Yes|Filter for content of searched messages|


### Return type: [Messages](../types/Messages.md)

