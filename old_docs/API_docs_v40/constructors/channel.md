---
title: channel
description: channel attributes, type and example
---
## Constructor: channel  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|:-------------:|---------:|
|id|[int](../types/int.md) | Required|
|access\_hash|[long](../types/long.md) | Required|
|title|[string](../types/string.md) | Required|
|photo|[ChatPhoto](../types/ChatPhoto.md) | Required|
|date|[int](../types/int.md) | Required|
|version|[int](../types/int.md) | Required|



### Type: [Chat](../types/Chat.md)


### Example:

```
$channel = ['_' => 'channel', 'id' => int, 'access_hash' => long, 'title' => string, 'photo' => ChatPhoto, 'date' => int, 'version' => int, ];
```  

