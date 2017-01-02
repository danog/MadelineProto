---
title: messageService
description: messageService attributes, type and example
---
## Constructor: messageService  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|:-------------:|---------:|
|unread|[Bool](../types/Bool.md) | Optional|
|out|[Bool](../types/Bool.md) | Optional|
|mentioned|[Bool](../types/Bool.md) | Optional|
|media\_unread|[Bool](../types/Bool.md) | Optional|
|id|[int](../types/int.md) | Required|
|from\_id|[int](../types/int.md) | Optional|
|to\_id|[Peer](../types/Peer.md) | Required|
|date|[int](../types/int.md) | Required|
|action|[MessageAction](../types/MessageAction.md) | Required|



### Type: [MTMessage](../types/MTMessage.md)


### Example:

```
$messageService = ['_' => 'messageService', 'unread' => true, 'out' => true, 'mentioned' => true, 'media_unread' => true, 'id' => int, 'from_id' => int, 'to_id' => Peer, 'date' => int, 'action' => MessageAction, ];
```  

