## Constructor: messages\_dialogsSlice  

### Attributes:

| Name     |    Type       | Required |
|----------|:-------------:|---------:|
|count|[int](../types/int.md) | Required|
|dialogs|Array of [Dialog](../types/Dialog.md) | Required|
|messages|Array of [Message](../types/Message.md) | Required|
|chats|Array of [Chat](../types/Chat.md) | Required|
|users|Array of [User](../types/User.md) | Required|


### Type: [messages\_Dialogs](../types/messages\_Dialogs.md)

### Example:


```
$messages_dialogsSlice = ['count' => int, 'dialogs' => [Dialog], 'messages' => [Message], 'chats' => [Chat], 'users' => [User], ];
```