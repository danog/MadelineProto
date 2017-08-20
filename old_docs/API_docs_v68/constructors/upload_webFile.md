---
title: upload.webFile
description: upload_webFile attributes, type and example
---
## Constructor: upload.webFile  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|---------------|----------|
|size|[int](../types/int.md) | Yes|
|mime\_type|[string](../types/string.md) | Yes|
|file\_type|[storage\_FileType](../types/storage_FileType.md) | Yes|
|mtime|[int](../types/int.md) | Yes|
|bytes|[bytes](../types/bytes.md) | Yes|



### Type: [upload\_WebFile](../types/upload_WebFile.md)


### Example:

```
$upload_webFile = ['_' => 'upload.webFile', 'size' => int, 'mime_type' => 'string', 'file_type' => storage_FileType, 'mtime' => int, 'bytes' => 'bytes'];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "upload.webFile", "size": int, "mime_type": "string", "file_type": storage_FileType, "mtime": int, "bytes": "bytes"}
```


Or, if you're into Lua:  


```
upload_webFile={_='upload.webFile', size=int, mime_type='string', file_type=storage_FileType, mtime=int, bytes='bytes'}

```


