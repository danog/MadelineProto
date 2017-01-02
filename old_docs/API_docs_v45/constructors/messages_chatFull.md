---
title: messages_chatFull
description: messages_chatFull attributes, type and example
---
## Constructor: messages\_chatFull  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|:-------------:|---------:|
|full\_chat|[ChatFull](../types/ChatFull.md) | Required|
|chats|Array of [Chat](../types/Chat.md) | Required|
|users|Array of [User](../types/User.md) | Required|



### Type: [messages\_ChatFull](../types/messages_ChatFull.md)


### Example:

```
$messages_chatFull = ['_' => 'messages_chatFull', 'full_chat' => ChatFull, 'chats' => [Vector t], 'users' => [Vector t], ];
```  

