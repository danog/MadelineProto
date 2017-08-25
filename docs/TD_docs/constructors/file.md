---
title: file
description: Represents a file
---
## Constructor: file  
[Back to constructors index](index.md)



Represents a file

### Attributes:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|id|[int](../types/int.md) | Yes|Unique file identifier|
|persistent\_id|[string](../types/string.md) | Yes|Persistent file identifier, if exists. Can be used across application restarts or even other devices for current logged user. If begins with "http: " or "https: ", it is HTTP URL of the file. Currently, TDLib is unable to download files if only they URL is known. If downloadFile is called on a such file or it is sended to a secret chat TDLib starts file generation process by sending to the client updateFileGenerationStart with HTTP URL in the original_path and "#url#" as conversion string. Client supposed to generate the file by downloading it to the specified location|
|size|[int](../types/int.md) | Yes|File size, 0 if unknown|
|is\_being\_downloaded|[Bool](../types/Bool.md) | Yes|True, if the file is currently being downloaded|
|local\_size|[int](../types/int.md) | Yes|Size of locally available part of the file. If size != 0 && local_size == size, full file is available locally|
|is\_being\_uploaded|[Bool](../types/Bool.md) | Yes|True, if the file is currently being uploaded|
|remote\_size|[int](../types/int.md) | Yes|Size of remotely available part of the file. If size != 0 && remote_size == size, the file is available remotely|
|path|[string](../types/string.md) | Yes|Local path to the available file part, may be empty|



### Type: [File](../types/File.md)


