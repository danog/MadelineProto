---
title: messages_statedMessages
description: messages_statedMessages attributes, type and example
---
## Constructor: messages\_statedMessages  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|:-------------:|---------:|
|messages|Array of [Message](../types/Message.md) | Required|
|chats|Array of [Chat](../types/Chat.md) | Required|
|users|Array of [User](../types/User.md) | Required|
|pts|[int](../types/int.md) | Required|
|seq|[int](../types/int.md) | Required|



### Type: [messages\_StatedMessages](../types/messages_StatedMessages.md)


### Example:

```
$messages_statedMessages = ['_' => 'messages_statedMessages', 'messages' => [Vector t], 'chats' => [Vector t], 'users' => [Vector t], 'pts' => int, 'seq' => int, ];
```  

