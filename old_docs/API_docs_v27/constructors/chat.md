---
title: chat
description: chat attributes, type and example
---
## Constructor: chat  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|:-------------:|---------:|
|id|[int](../types/int.md) | Required|
|title|[string](../types/string.md) | Required|
|photo|[ChatPhoto](../types/ChatPhoto.md) | Required|
|participants\_count|[int](../types/int.md) | Required|
|date|[int](../types/int.md) | Required|
|left|[Bool](../types/Bool.md) | Required|
|version|[int](../types/int.md) | Required|



### Type: [Chat](../types/Chat.md)


### Example:

```
$chat = ['_' => 'chat', 'id' => int, 'title' => string, 'photo' => ChatPhoto, 'participants_count' => int, 'date' => int, 'left' => Bool, 'version' => int, ];
```  

