---
title: messages_statedMessageLink
description: messages_statedMessageLink attributes, type and example
---
## Constructor: messages\_statedMessageLink  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|:-------------:|---------:|
|message|[Message](../types/Message.md) | Required|
|chats|Array of [Chat](../types/Chat.md) | Required|
|users|Array of [User](../types/User.md) | Required|
|links|Array of [contacts\_Link](../types/contacts_Link.md) | Required|
|pts|[int](../types/int.md) | Required|
|seq|[int](../types/int.md) | Required|



### Type: [messages\_StatedMessage](../types/messages_StatedMessage.md)


### Example:

```
$messages_statedMessageLink = ['_' => 'messages_statedMessageLink', 'message' => Message, 'chats' => [Vector t], 'users' => [Vector t], 'links' => [Vector t], 'pts' => int, 'seq' => int, ];
```  

