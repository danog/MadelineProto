---
title: uploadFile
description: Asynchronously uploads file to the cloud without sending it in a message. Updates updateFile will notify about upload progress and successful upload. The file will not have persistent identifier until it will be sent in a message
---
## Method: uploadFile  
[Back to methods index](index.md)


YOU CANNOT USE THIS METHOD IN MADELINEPROTO


Asynchronously uploads file to the cloud without sending it in a message. Updates updateFile will notify about upload progress and successful upload. The file will not have persistent identifier until it will be sent in a message

### Params:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|file|[InputFile](../types/InputFile.md) | Yes|File to upload|
|file\_type|[FileType](../types/FileType.md) | Yes|File type|
|priority|[int](../types/int.md) | Yes|Priority of upload, 1-32. The higher priority, the earlier file will be uploaded. If priorities of two files are equal then the first one for which uploadFile is called will be uploaded first|


### Return type: [File](../types/File.md)

