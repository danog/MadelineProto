---
title: message
description: message attributes, type and example
---
## Constructor: message  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|:-------------:|---------:|
|id|[int](../types/int.md) | Required|
|from\_id|[int](../types/int.md) | Required|
|to\_id|[Peer](../types/Peer.md) | Required|
|date|[int](../types/int.md) | Required|
|message|[string](../types/string.md) | Required|
|media|[MessageMedia](../types/MessageMedia.md) | Required|



### Type: [Message](../types/Message.md)


### Example:

```
$message = ['_' => 'message', 'id' => int, 'from_id' => int, 'to_id' => Peer, 'date' => int, 'message' => string, 'media' => MessageMedia, ];
```  

