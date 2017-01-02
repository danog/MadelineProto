---
title: messageService
description: messageService attributes, type and example
---
## Constructor: messageService  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|:-------------:|---------:|
|id|[int](../types/int.md) | Required|
|from\_id|[int](../types/int.md) | Optional|
|to\_id|[Peer](../types/Peer.md) | Required|
|date|[int](../types/int.md) | Required|
|action|[MessageAction](../types/MessageAction.md) | Required|



### Type: [Message](../types/Message.md)


### Example:

```
$messageService = ['_' => 'messageService', 'id' => int, 'from_id' => int, 'to_id' => Peer, 'date' => int, 'action' => MessageAction, ];
```  

