---
title: getCommonChats
description: Returns list of common chats with an other given user. Chats are sorted by their type and creation date
---
## Method: getCommonChats  
[Back to methods index](index.md)


YOU CANNOT USE THIS METHOD IN MADELINEPROTO


Returns list of common chats with an other given user. Chats are sorted by their type and creation date

### Params:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|user\_id|[int](../types/int.md) | Yes|User identifier|
|offset\_chat\_id|[int53](../types/int53.md) | Yes|Chat identifier to return chats from, use 0 for the first request|
|limit|[int](../types/int.md) | Yes|Maximum number of chats to be returned, up to 100|


### Return type: [Chats](../types/Chats.md)

