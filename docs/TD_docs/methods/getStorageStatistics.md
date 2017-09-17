---
title: getStorageStatistics
description: Returns storage usage statistics
---
## Method: getStorageStatistics  
[Back to methods index](index.md)


YOU CANNOT USE THIS METHOD IN MADELINEPROTO


Returns storage usage statistics

### Params:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|chat\_limit|[int](../types/int.md) | Yes|Maximum number of chats with biggest storage usage for which separate statistics should be returned. All other chats will be grouped in entries with chat_id == 0. If chat info database is not used, chat_limit is ignored and is always set to 0|


### Return type: [StorageStatistics](../types/StorageStatistics.md)

