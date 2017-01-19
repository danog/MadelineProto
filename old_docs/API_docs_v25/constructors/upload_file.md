---
title: upload.file
description: upload_file attributes, type and example
---
## Constructor: upload.file  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|:-------------:|---------:|
|type|[storage\_FileType](../types/storage_FileType.md) | Required|
|mtime|[int](../types/int.md) | Required|
|bytes|[bytes](../types/bytes.md) | Required|



### Type: [upload\_File](../types/upload_File.md)


### Example:

```
$upload_file = ['_' => 'upload.file', 'type' => storage.FileType, 'mtime' => int, 'bytes' => bytes, ];
```  

