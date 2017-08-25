---
title: storageStatistics
description: Contains exact storage usage statistics splitted by chats and file types
---
## Constructor: storageStatistics  
[Back to constructors index](index.md)



Contains exact storage usage statistics splitted by chats and file types

### Attributes:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|size|[int53](../types/int53.md) | Yes|Total size of files|
|count|[int](../types/int.md) | Yes|Total number of files|
|by\_chat|Array of [storageStatisticsByChat](../constructors/storageStatisticsByChat.md) | Yes|Statistics splitted by chats|



### Type: [StorageStatistics](../types/StorageStatistics.md)


