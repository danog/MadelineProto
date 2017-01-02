---
title: geoChatMessage
description: geoChatMessage attributes, type and example
---
## Constructor: geoChatMessage  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|:-------------:|---------:|
|chat\_id|[int](../types/int.md) | Required|
|id|[int](../types/int.md) | Required|
|from\_id|[int](../types/int.md) | Required|
|date|[int](../types/int.md) | Required|
|message|[string](../types/string.md) | Required|
|media|[MessageMedia](../types/MessageMedia.md) | Required|



### Type: [GeoChatMessage](../types/GeoChatMessage.md)


### Example:

```
$geoChatMessage = ['_' => 'geoChatMessage', 'chat_id' => int, 'id' => int, 'from_id' => int, 'date' => int, 'message' => string, 'media' => MessageMedia, ];
```  

