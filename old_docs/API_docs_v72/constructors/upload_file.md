---
title: upload.file
description: upload_file attributes, type and example
---
## Constructor: upload.file  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|---------------|----------|
|type|[storage\_FileType](../types/storage_FileType.md) | Yes|
|mtime|[int](../types/int.md) | Yes|
|bytes|[bytes](../types/bytes.md) | Yes|



### Type: [upload\_File](../types/upload_File.md)


### Example:

```
$upload_file = ['_' => 'upload.file', 'type' => storage_FileType, 'mtime' => int, 'bytes' => 'bytes'];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "upload.file", "type": storage_FileType, "mtime": int, "bytes": "bytes"}
```


Or, if you're into Lua:  


```
upload_file={_='upload.file', type=storage_FileType, mtime=int, bytes='bytes'}

```


