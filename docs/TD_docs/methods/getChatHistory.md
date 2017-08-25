---
title: getChatHistory
description: Returns messages in a chat. Automatically calls openChat. Returns result in reverse chronological order, i.e. in order of decreasing message.message_id. Offline request if only_local is true
---
## Method: getChatHistory  
[Back to methods index](index.md)


YOU CANNOT USE THIS METHOD IN MADELINEPROTO


Returns messages in a chat. Automatically calls openChat. Returns result in reverse chronological order, i.e. in order of decreasing message.message_id. Offline request if only_local is true

### Params:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|chat\_id|[InputPeer](../types/InputPeer.md) | Yes|Chat identifier|
|from\_message\_id|[int53](../types/int53.md) | Yes|Identifier of the message near which we need a history, you can use 0 to get results from the beginning, i.e. from oldest to newest|
|offset|[int](../types/int.md) | Yes|Specify 0 to get results exactly from from_message_id or negative offset to get specified message and some newer messages|
|limit|[int](../types/int.md) | Yes|Maximum number of messages to be returned, should be positive and can't be greater than 100. If offset is negative, limit must be greater than -offset. There may be less than limit messages returned even the end of the history is not reached|
|only\_local|[Bool](../types/Bool.md) | Yes|Return only locally available messages without sending network requests|


### Return type: [Messages](../types/Messages.md)

