---
title: inputEncryptedFileUploaded
description: inputEncryptedFileUploaded attributes, type and example
---
## Constructor: inputEncryptedFileUploaded  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|:-------------:|---------:|
|id|[long](../types/long.md) | Required|
|parts|[int](../types/int.md) | Required|
|md5\_checksum|[string](../types/string.md) | Required|
|key\_fingerprint|[int](../types/int.md) | Required|



### Type: [InputEncryptedFile](../types/InputEncryptedFile.md)


### Example:

```
$inputEncryptedFileUploaded = ['_' => inputEncryptedFileUploaded, 'id' => long, 'parts' => int, 'md5_checksum' => string, 'key_fingerprint' => int, ];
```