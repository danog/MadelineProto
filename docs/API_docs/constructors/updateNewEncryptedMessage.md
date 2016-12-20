---
title: updateNewEncryptedMessage
description: updateNewEncryptedMessage attributes, type and example
---
## Constructor: updateNewEncryptedMessage  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|:-------------:|---------:|
|message|[EncryptedMessage](../types/EncryptedMessage.md) | Required|
|qts|[int](../types/int.md) | Required|



### Type: [Update](../types/Update.md)


### Example:

```
$updateNewEncryptedMessage = ['_' => updateNewEncryptedMessage', 'message' => EncryptedMessage, 'qts' => int, ];
```