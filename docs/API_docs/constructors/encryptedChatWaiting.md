---
title: encryptedChatWaiting
description: encryptedChatWaiting attributes, type and example
---
## Constructor: encryptedChatWaiting  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|:-------------:|---------:|
|id|[int](../types/int.md) | Required|
|access\_hash|[long](../types/long.md) | Required|
|date|[int](../types/int.md) | Required|
|admin\_id|[int](../types/int.md) | Required|
|participant\_id|[int](../types/int.md) | Required|



### Type: [EncryptedChat](../types/EncryptedChat.md)


### Example:

```
$encryptedChatWaiting = ['_' => encryptedChatWaiting', 'id' => int, 'access_hash' => long, 'date' => int, 'admin_id' => int, 'participant_id' => int, ];
```