---
title: searchChatMessages
description: Searches for messages with given words in the chat. Returns result in reverse chronological order, i. e. in order of decreasing message_id. Doesn't work in secret chats with non-empty query (searchSecretMessages should be used instead) or without enabled message database
---
## Method: searchChatMessages  
[Back to methods index](index.md)


YOU CANNOT USE THIS METHOD IN MADELINEPROTO


Searches for messages with given words in the chat. Returns result in reverse chronological order, i. e. in order of decreasing message_id. Doesn't work in secret chats with non-empty query (searchSecretMessages should be used instead) or without enabled message database

### Params:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|chat\_id|[InputPeer](../types/InputPeer.md) | Yes|Chat identifier to search in|
|query|[string](../types/string.md) | Yes|Query to search for|
|sender\_user\_id|[int](../types/int.md) | Yes|If not 0, only messages sent by the specified user will be returned. Doesn't supported in secret chats|
|from\_message\_id|[int53](../types/int53.md) | Yes|Identifier of the message from which we need a history, you can use 0 to get results from the beginning|
|limit|[int](../types/int.md) | Yes|Maximum number of messages to be returned, can't be greater than 100. There may be less than limit messages returned even the end of the history is not reached|
|filter|[SearchMessagesFilter](../types/SearchMessagesFilter.md) | Yes|Filter for content of searched messages|


### Return type: [Messages](../types/Messages.md)

