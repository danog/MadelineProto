## Constructor: messages\_channelMessages  

### Attributes:

| Name     |    Type       | Required |
|----------|:-------------:|---------:|
|pts|[int](../types/int.md) | Required|
|count|[int](../types/int.md) | Required|
|messages|Array of [Message](../types/Message.md) | Required|
|chats|Array of [Chat](../types/Chat.md) | Required|
|users|Array of [User](../types/User.md) | Required|


### Type: [messages\_Messages](../types/messages\_Messages.md)

### Example:


```
$messages_channelMessages = ['pts' => int, 'count' => int, 'messages' => [Message], 'chats' => [Chat], 'users' => [User], ];
```