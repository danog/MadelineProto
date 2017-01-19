---
title: messages.statedMessagesLinks
description: messages_statedMessagesLinks attributes, type and example
---
## Constructor: messages.statedMessagesLinks  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|:-------------:|---------:|
|messages|Array of [Message](../types/Message.md) | Required|
|chats|Array of [Chat](../types/Chat.md) | Required|
|users|Array of [User](../types/User.md) | Required|
|pts|[int](../types/int.md) | Required|
|pts\_count|[int](../types/int.md) | Required|
|links|Array of [contacts\_Link](../types/contacts_Link.md) | Required|
|seq|[int](../types/int.md) | Required|



### Type: [messages\_StatedMessages](../types/messages_StatedMessages.md)


### Example:

```
$messages_statedMessagesLinks = ['_' => 'messages.statedMessagesLinks', 'messages' => [Vector t], 'chats' => [Vector t], 'users' => [Vector t], 'pts' => int, 'pts_count' => int, 'links' => [Vector t], 'seq' => int, ];
```  

