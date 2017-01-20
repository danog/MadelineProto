---
title: messages.statedMessage
description: messages_statedMessage attributes, type and example
---
## Constructor: messages.statedMessage  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|:-------------:|---------:|
|message|[Message](../types/Message.md) | Required|
|chats|Array of [Chat](../types/Chat.md) | Required|
|users|Array of [User](../types/User.md) | Required|
|pts|[int](../types/int.md) | Required|
|pts\_count|[int](../types/int.md) | Required|



### Type: [messages\_StatedMessage](../types/messages_StatedMessage.md)


### Example:

```
$messages_statedMessage = ['_' => 'messages.statedMessage', 'message' => Message, 'chats' => [Vector t], 'users' => [Vector t], 'pts' => int, 'pts_count' => int, ];
```  

