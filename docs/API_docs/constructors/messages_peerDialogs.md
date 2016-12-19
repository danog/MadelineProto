## Constructor: messages\_peerDialogs  

### Attributes:

| Name     |    Type       | Required |
|----------|:-------------:|---------:|
|dialogs|Array of [Dialog](../types/Dialog.md) | Required|
|messages|Array of [Message](../types/Message.md) | Required|
|chats|Array of [Chat](../types/Chat.md) | Required|
|users|Array of [User](../types/User.md) | Required|
|state|[updates\_State](../types/updates\_State.md) | Required|


### Type: [messages\_PeerDialogs](../types/messages\_PeerDialogs.md)

### Example:


```
$messages_peerDialogs = ['_' => messages_peerDialogs', 'dialogs' => [Vector t], 'messages' => [Vector t], 'chats' => [Vector t], 'users' => [Vector t], 'state' => updates.State, ];
```