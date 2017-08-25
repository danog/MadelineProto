---
title: storageStatisticsByChat
description: Contains storage usage statistics for the specific chat
---
## Constructor: storageStatisticsByChat  
[Back to constructors index](index.md)



Contains storage usage statistics for the specific chat

### Attributes:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|chat\_id|[int53](../types/int53.md) | Yes|Chat identifier, 0 if none|
|size|[int53](../types/int53.md) | Yes|Total size of files|
|count|[int](../types/int.md) | Yes|Total number of files|
|by\_file\_type|Array of [storageStatisticsByFileType](../constructors/storageStatisticsByFileType.md) | Yes|Statistics splitted by file types|



### Type: [StorageStatisticsByChat](../types/StorageStatisticsByChat.md)


