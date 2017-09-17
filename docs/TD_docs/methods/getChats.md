---
title: getChats
description: Returns list of chats in the right order, chats are sorted by (order, chat_id) in decreasing order. For example, to get list of chats from the beginning, the offset_order should be equal 2^63 - 1
---
## Method: getChats  
[Back to methods index](index.md)


YOU CANNOT USE THIS METHOD IN MADELINEPROTO


Returns list of chats in the right order, chats are sorted by (order, chat_id) in decreasing order. For example, to get list of chats from the beginning, the offset_order should be equal 2^63 - 1

### Params:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|offset\_order|[long](../types/long.md) | Yes|Chat order to return chats from|
|offset\_chat\_id|[int53](../types/int53.md) | Yes|Chat identifier to return chats from|
|limit|[int](../types/int.md) | Yes|Maximum number of chats to be returned. There may be less than limit chats returned even the end of the list is not reached|


### Return type: [Chats](../types/Chats.md)

