---
title: messages_channelMessages
description: messages_channelMessages attributes, type and example
---
## Constructor: messages\_channelMessages  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|:-------------:|---------:|
|pts|[int](../types/int.md) | Required|
|count|[int](../types/int.md) | Required|
|messages|Array of [Message](../types/Message.md) | Required|
|collapsed|Array of [MessageGroup](../types/MessageGroup.md) | Optional|
|chats|Array of [Chat](../types/Chat.md) | Required|
|users|Array of [User](../types/User.md) | Required|



### Type: [messages\_Messages](../types/messages_Messages.md)


### Example:

```
$messages_channelMessages = ['_' => 'messages_channelMessages', 'pts' => int, 'count' => int, 'messages' => [Vector t], 'collapsed' => [Vector t], 'chats' => [Vector t], 'users' => [Vector t], ];
```  

