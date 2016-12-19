## Constructor: encryptedMessage  

### Attributes:

| Name     |    Type       | Required |
|----------|:-------------:|---------:|
|random\_id|[long](../types/long.md) | Required|
|chat\_id|[int](../types/int.md) | Required|
|date|[int](../types/int.md) | Required|
|bytes|[bytes](../types/bytes.md) | Required|
|file|[EncryptedFile](../types/EncryptedFile.md) | Required|



### Type: [EncryptedMessage](../types/EncryptedMessage.md)


### Example:

```
$encryptedMessage = ['_' => encryptedMessage', 'random_id' => long, 'chat_id' => int, 'date' => int, 'bytes' => bytes, 'file' => EncryptedFile, ];
```