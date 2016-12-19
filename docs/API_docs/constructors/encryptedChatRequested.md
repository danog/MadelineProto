## Constructor: encryptedChatRequested  

### Attributes:

| Name     |    Type       | Required |
|----------|:-------------:|---------:|
|id|[int](../types/int.md) | Required|
|access\_hash|[long](../types/long.md) | Required|
|date|[int](../types/int.md) | Required|
|admin\_id|[int](../types/int.md) | Required|
|participant\_id|[int](../types/int.md) | Required|
|g\_a|[bytes](../types/bytes.md) | Required|
### Type: 

[EncryptedChat](../types/EncryptedChat.md)
### Example:

```
$encryptedChatRequested = ['_' => encryptedChatRequested', 'id' => int, 'access_hash' => long, 'date' => int, 'admin_id' => int, 'participant_id' => int, 'g_a' => bytes, ];
```