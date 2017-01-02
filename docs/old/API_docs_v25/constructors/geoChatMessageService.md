---
title: geoChatMessageService
description: geoChatMessageService attributes, type and example
---
## Constructor: geoChatMessageService  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|:-------------:|---------:|
|chat\_id|[int](../types/int.md) | Required|
|id|[int](../types/int.md) | Required|
|from\_id|[int](../types/int.md) | Required|
|date|[int](../types/int.md) | Required|
|action|[MessageAction](../types/MessageAction.md) | Required|



### Type: [GeoChatMessage](../types/GeoChatMessage.md)


### Example:

```
$geoChatMessageService = ['_' => 'geoChatMessageService', 'chat_id' => int, 'id' => int, 'from_id' => int, 'date' => int, 'action' => MessageAction, ];
```  

