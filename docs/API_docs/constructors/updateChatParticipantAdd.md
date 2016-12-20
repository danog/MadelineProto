---
title: updateChatParticipantAdd
description: updateChatParticipantAdd attributes, type and example
---
## Constructor: updateChatParticipantAdd  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|:-------------:|---------:|
|chat\_id|[int](../types/int.md) | Required|
|user\_id|[int](../types/int.md) | Required|
|inviter\_id|[int](../types/int.md) | Required|
|date|[int](../types/int.md) | Required|
|version|[int](../types/int.md) | Required|



### Type: [Update](../types/Update.md)


### Example:

```
$updateChatParticipantAdd = ['_' => updateChatParticipantAdd', 'chat_id' => int, 'user_id' => int, 'inviter_id' => int, 'date' => int, 'version' => int, ];
```