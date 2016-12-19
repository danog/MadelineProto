## Constructor: encryptedChat  

### Attributes:

| Name     |    Type       | Required |
|----------|:-------------:|---------:|
|id|[int](../types/int.md) | Required|
|access\_hash|[long](../types/long.md) | Required|
|date|[int](../types/int.md) | Required|
|admin\_id|[int](../types/int.md) | Required|
|participant\_id|[int](../types/int.md) | Required|
|g\_a\_or\_b|[bytes](../types/bytes.md) | Required|
|key\_fingerprint|[long](../types/long.md) | Required|
### Type: 

[EncryptedChat](../types/EncryptedChat.md)
### Example:

```
$encryptedChat = ['_' => encryptedChat', 'id' => int, 'access_hash' => long, 'date' => int, 'admin_id' => int, 'participant_id' => int, 'g_a_or_b' => bytes, 'key_fingerprint' => long, ];
```