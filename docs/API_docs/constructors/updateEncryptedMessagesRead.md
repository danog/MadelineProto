## Constructor: updateEncryptedMessagesRead  

### Attributes:

| Name     |    Type       | Required |
|----------|:-------------:|---------:|
|chat\_id|[int](../types/int.md) | Required|
|max\_date|[int](../types/int.md) | Required|
|date|[int](../types/int.md) | Required|
### Type: 

[Update](../types/Update.md)
### Example:

```
$updateEncryptedMessagesRead = ['_' => updateEncryptedMessagesRead', 'chat_id' => int, 'max_date' => int, 'date' => int, ];
```