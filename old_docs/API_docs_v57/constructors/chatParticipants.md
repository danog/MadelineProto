---
title: chatParticipants
description: chatParticipants attributes, type and example
---
## Constructor: chatParticipants  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|:-------------:|---------:|
|chat\_id|[int](../types/int.md) | Required|
|participants|Array of [ChatParticipant](../types/ChatParticipant.md) | Required|
|version|[int](../types/int.md) | Required|



### Type: [ChatParticipants](../types/ChatParticipants.md)


### Example:

```
$chatParticipants = ['_' => 'chatParticipants', 'chat_id' => int, 'participants' => [Vector t], 'version' => int, ];
```  

