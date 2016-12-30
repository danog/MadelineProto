---
title: encryptedMessage
description: encryptedMessage attributes, type and example
---
## Constructor: encryptedMessage  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|:-------------:|---------:|
|chat\_id|[int](../types/int.md) | Required|
|date|[int](../types/int.md) | Required|
|bytes|[bytes](../types/bytes.md) | Required|
|file|[EncryptedFile](../types/EncryptedFile.md) | Required|



### Type: [EncryptedMessage](../types/EncryptedMessage.md)


### Example:

```
$encryptedMessage = ['_' => 'encryptedMessage', 'chat_id' => int, 'date' => int, 'bytes' => bytes, 'file' => EncryptedFile, ];
```