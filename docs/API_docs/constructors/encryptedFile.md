---
title: encryptedFile
---
## Constructor: encryptedFile  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|:-------------:|---------:|
|id|[long](../types/long.md) | Required|
|access\_hash|[long](../types/long.md) | Required|
|size|[int](../types/int.md) | Required|
|dc\_id|[int](../types/int.md) | Required|
|key\_fingerprint|[int](../types/int.md) | Required|



### Type: [EncryptedFile](../types/EncryptedFile.md)


### Example:

```
$encryptedFile = ['_' => encryptedFile', 'id' => long, 'access_hash' => long, 'size' => int, 'dc_id' => int, 'key_fingerprint' => int, ];
```