## Constructor: updates\_differenceSlice  

### Attributes:

| Name     |    Type       | Required |
|----------|:-------------:|---------:|
|new\_messages|Array of [Message](../types/Message.md) | Required|
|new\_encrypted\_messages|Array of [EncryptedMessage](../types/EncryptedMessage.md) | Required|
|other\_updates|Array of [Update](../types/Update.md) | Required|
|chats|Array of [Chat](../types/Chat.md) | Required|
|users|Array of [User](../types/User.md) | Required|
|intermediate\_state|[updates\_State](../types/updates_State.md) | Required|



### Type: [updates\_Difference](../types/updates_Difference.md)


### Example:

```
$updates_differenceSlice = ['_' => updates_differenceSlice', 'new_messages' => [Vector t], 'new_encrypted_messages' => [Vector t], 'other_updates' => [Vector t], 'chats' => [Vector t], 'users' => [Vector t], 'intermediate_state' => updates.State, ];
```