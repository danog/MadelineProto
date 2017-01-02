---
title: updateShortChatMessage
description: updateShortChatMessage attributes, type and example
---
## Constructor: updateShortChatMessage  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|:-------------:|---------:|
|id|[int](../types/int.md) | Required|
|from\_id|[int](../types/int.md) | Required|
|chat\_id|[int](../types/int.md) | Required|
|message|[string](../types/string.md) | Required|
|pts|[int](../types/int.md) | Required|
|date|[int](../types/int.md) | Required|
|seq|[int](../types/int.md) | Required|



### Type: [Updates](../types/Updates.md)


### Example:

```
$updateShortChatMessage = ['_' => 'updateShortChatMessage', 'id' => int, 'from_id' => int, 'chat_id' => int, 'message' => string, 'pts' => int, 'date' => int, 'seq' => int, ];
```  

