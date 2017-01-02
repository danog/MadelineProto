---
title: audio
description: audio attributes, type and example
---
## Constructor: audio  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|:-------------:|---------:|
|id|[long](../types/long.md) | Required|
|access\_hash|[long](../types/long.md) | Required|
|user\_id|[int](../types/int.md) | Required|
|date|[int](../types/int.md) | Required|
|duration|[int](../types/int.md) | Required|
|mime\_type|[string](../types/string.md) | Required|
|size|[int](../types/int.md) | Required|
|dc\_id|[int](../types/int.md) | Required|



### Type: [Audio](../types/Audio.md)


### Example:

```
$audio = ['_' => 'audio', 'id' => long, 'access_hash' => long, 'user_id' => int, 'date' => int, 'duration' => int, 'mime_type' => string, 'size' => int, 'dc_id' => int, ];
```  

