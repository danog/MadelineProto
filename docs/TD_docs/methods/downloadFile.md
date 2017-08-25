---
title: downloadFile
description: Asynchronously downloads file from cloud. Updates updateFile will notify about download progress and successful download
---
## Method: downloadFile  
[Back to methods index](index.md)


YOU CANNOT USE THIS METHOD IN MADELINEPROTO


Asynchronously downloads file from cloud. Updates updateFile will notify about download progress and successful download

### Params:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|file\_id|[int](../types/int.md) | Yes|Identifier of file to download|
|priority|[int](../types/int.md) | Yes|Priority of download, 1-32. The higher priority, the earlier file will be downloaded. If priorities of two files are equal then the last one for which downloadFile is called will be downloaded first|


### Return type: [Ok](../types/Ok.md)

