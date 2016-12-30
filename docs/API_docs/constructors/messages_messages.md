---
title: messages_messages
description: messages_messages attributes, type and example
---
## Constructor: messages\_messages  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|:-------------:|---------:|
|messages|Array of [Message](../types/Message.md) | Required|
|chats|Array of [Chat](../types/Chat.md) | Required|
|users|Array of [User](../types/User.md) | Required|



### Type: [messages\_Messages](../types/messages_Messages.md)


### Example:

```
$messages_messages = ['_' => 'messages_messages', 'messages' => [Vector t], 'chats' => [Vector t], 'users' => [Vector t], ];
```