## Constructor: messages\_chatFull  

### Attributes:

| Name     |    Type       | Required |
|----------|:-------------:|---------:|
|full\_chat|[ChatFull](../types/ChatFull.md) | Required|
|chats|Array of [Chat](../types/Chat.md) | Required|
|users|Array of [User](../types/User.md) | Required|


### Type: [messages\_ChatFull](../types/messages\_ChatFull.md)

### Example:


```
$messages_chatFull = ['full_chat' => ChatFull, 'chats' => [Chat], 'users' => [User], ];
```