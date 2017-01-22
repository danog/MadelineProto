---
title: geochats.messagesSlice
description: geochats_messagesSlice attributes, type and example
---
## Constructor: geochats.messagesSlice  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|:-------------:|---------:|
|count|[int](../types/int.md) | Required|
|messages|Array of [GeoChatMessage](../types/GeoChatMessage.md) | Required|
|chats|Array of [Chat](../types/Chat.md) | Required|
|users|Array of [User](../types/User.md) | Required|



### Type: [geochats\_Messages](../types/geochats_Messages.md)


### Example:

```
$geochats_messagesSlice = ['_' => 'geochats.messagesSlice', 'count' => int, 'messages' => [Vector t], 'chats' => [Vector t], 'users' => [Vector t], ];
```  

