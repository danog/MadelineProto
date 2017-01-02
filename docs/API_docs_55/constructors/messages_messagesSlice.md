---
title: messages_messagesSlice
description: messages_messagesSlice attributes, type and example
---
## Constructor: messages\_messagesSlice  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|:-------------:|---------:|
|count|[int](../types/int.md) | Required|
|messages|Array of [Message](../types/Message.md) | Required|
|chats|Array of [Chat](../types/Chat.md) | Required|
|users|Array of [User](../types/User.md) | Required|



### Type: [messages\_Messages](../types/messages_Messages.md)


### Example:

```
$messages_messagesSlice = ['_' => 'messages_messagesSlice', 'count' => int, 'messages' => [Vector t], 'chats' => [Vector t], 'users' => [Vector t], ];
```