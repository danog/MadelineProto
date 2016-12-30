---
title: encryptedMessageService
description: encryptedMessageService attributes, type and example
---
## Constructor: encryptedMessageService  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|:-------------:|---------:|
|chat\_id|[int](../types/int.md) | Required|
|date|[int](../types/int.md) | Required|
|bytes|[bytes](../types/bytes.md) | Required|



### Type: [EncryptedMessage](../types/EncryptedMessage.md)


### Example:

```
$encryptedMessageService = ['_' => encryptedMessageService, 'chat_id' => int, 'date' => int, 'bytes' => bytes, ];
```