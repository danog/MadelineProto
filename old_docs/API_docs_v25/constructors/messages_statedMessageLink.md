---
title: messages.statedMessageLink
description: messages_statedMessageLink attributes, type and example
---
## Constructor: messages.statedMessageLink  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|:-------------:|---------:|
|message|[Message](../types/Message.md) | Required|
|chats|Array of [Chat](../types/Chat.md) | Required|
|users|Array of [User](../types/User.md) | Required|
|pts|[int](../types/int.md) | Required|
|pts\_count|[int](../types/int.md) | Required|
|links|Array of [contacts\_Link](../types/contacts_Link.md) | Required|
|seq|[int](../types/int.md) | Required|



### Type: [messages\_StatedMessage](../types/messages_StatedMessage.md)


### Example:

```
$messages_statedMessageLink = ['_' => 'messages.statedMessageLink', 'message' => Message, 'chats' => [Vector t], 'users' => [Vector t], 'pts' => int, 'pts_count' => int, 'links' => [Vector t], 'seq' => int, ];
```  

