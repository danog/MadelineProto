---
title: geochats.located
description: geochats_located attributes, type and example
---
## Constructor: geochats.located  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|:-------------:|---------:|
|results|Array of [ChatLocated](../types/ChatLocated.md) | Required|
|messages|Array of [GeoChatMessage](../types/GeoChatMessage.md) | Required|
|chats|Array of [Chat](../types/Chat.md) | Required|
|users|Array of [User](../types/User.md) | Required|



### Type: [geochats\_Located](../types/geochats_Located.md)


### Example:

```
$geochats_located = ['_' => 'geochats.located', 'results' => [Vector t], 'messages' => [Vector t], 'chats' => [Vector t], 'users' => [Vector t], ];
```  

