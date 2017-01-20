---
title: geochats.statedMessage
description: geochats_statedMessage attributes, type and example
---
## Constructor: geochats.statedMessage  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|:-------------:|---------:|
|message|[GeoChatMessage](../types/GeoChatMessage.md) | Required|
|chats|Array of [Chat](../types/Chat.md) | Required|
|users|Array of [User](../types/User.md) | Required|
|seq|[int](../types/int.md) | Required|



### Type: [geochats\_StatedMessage](../types/geochats_StatedMessage.md)


### Example:

```
$geochats_statedMessage = ['_' => 'geochats.statedMessage', 'message' => GeoChatMessage, 'chats' => [Vector t], 'users' => [Vector t], 'seq' => int, ];
```  

