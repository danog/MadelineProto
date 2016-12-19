## Constructor: updates\_difference  

### Attributes:

| Name     |    Type       | Required |
|----------|:-------------:|---------:|
|new\_messages|Array of [Message](../types/Message.md) | Required|
|new\_encrypted\_messages|Array of [EncryptedMessage](../types/EncryptedMessage.md) | Required|
|other\_updates|Array of [Update](../types/Update.md) | Required|
|chats|Array of [Chat](../types/Chat.md) | Required|
|users|Array of [User](../types/User.md) | Required|
|state|[updates\_State](../types/updates\_State.md) | Required|


### Type: [updates\_Difference](../types/updates\_Difference.md)

### Example:


```
$updates_difference = ['_' => updates_difference', 'new_messages' => [Vector t], 'new_encrypted_messages' => [Vector t], 'other_updates' => [Vector t], 'chats' => [Vector t], 'users' => [Vector t], 'state' => updates.State, ];
```