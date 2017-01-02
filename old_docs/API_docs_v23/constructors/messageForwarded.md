---
title: messageForwarded
description: messageForwarded attributes, type and example
---
## Constructor: messageForwarded  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|:-------------:|---------:|
|id|[int](../types/int.md) | Required|
|fwd\_from\_id|[int](../types/int.md) | Required|
|fwd\_date|[int](../types/int.md) | Required|
|from\_id|[int](../types/int.md) | Required|
|to\_id|[Peer](../types/Peer.md) | Required|
|date|[int](../types/int.md) | Required|
|message|[string](../types/string.md) | Required|
|media|[MessageMedia](../types/MessageMedia.md) | Required|



### Type: [Message](../types/Message.md)


### Example:

```
$messageForwarded = ['_' => 'messageForwarded', 'id' => int, 'fwd_from_id' => int, 'fwd_date' => int, 'from_id' => int, 'to_id' => Peer, 'date' => int, 'message' => string, 'media' => MessageMedia, ];
```  

