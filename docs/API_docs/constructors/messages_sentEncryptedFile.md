---
title: messages_sentEncryptedFile
description: messages_sentEncryptedFile attributes, type and example
---
## Constructor: messages\_sentEncryptedFile  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|:-------------:|---------:|
|date|[int](../types/int.md) | Required|
|file|[EncryptedFile](../types/EncryptedFile.md) | Required|



### Type: [messages\_SentEncryptedMessage](../types/messages_SentEncryptedMessage.md)


### Example:

```
$messages_sentEncryptedFile = ['_' => messages_sentEncryptedFile', 'date' => int, 'file' => EncryptedFile, ];
```