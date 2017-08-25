---
title: optimizeStorage
description: Optimizes storage usage, i.e. deletes some files and return new storage usage statistics. Secret thumbnails can't be deleted
---
## Method: optimizeStorage  
[Back to methods index](index.md)


YOU CANNOT USE THIS METHOD IN MADELINEPROTO


Optimizes storage usage, i.e. deletes some files and return new storage usage statistics. Secret thumbnails can't be deleted

### Params:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|size|[int53](../types/int53.md) | Yes|Limit on total size of files after deletion. Pass -1 to use default limit|
|ttl|[int](../types/int.md) | Yes|Limit on time passed since last access time (or creation time on some filesystems) to a file. Pass -1 to use default limit|
|count|[int](../types/int.md) | Yes|Limit on total count of files after deletion. Pass -1 to use default limit|
|immunity\_delay|[int](../types/int.md) | Yes|Number of seconds after creation of a file, it can't be delited. Pass -1 to use default value|
|file\_types|Array of [FileType](../types/FileType.md) | Yes|If not empty, only files with given types are considered. By default, all types except thumbnails, profile photos, stickers and wallpapers are deleted|
|chat\_ids|Array of [int53](../types/int53.md) | Yes|If not empty, only files from the given chats are considered. Use 0 as chat identifier to delete files not belonging to any chat, for example profile photos|
|exclude\_chat\_ids|Array of [int53](../types/int53.md) | Yes|If not empty, files from the given chats are exluded. Use 0 as chat identifier to exclude all files not belonging to any chat, for example profile photos|
|chat\_limit|[int](../types/int.md) | Yes|Same as in getStorageStatistics. Affects only returned statistics|


### Return type: [StorageStatistics](../types/StorageStatistics.md)

