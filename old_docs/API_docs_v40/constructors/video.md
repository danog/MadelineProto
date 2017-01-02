---
title: video
description: video attributes, type and example
---
## Constructor: video  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|:-------------:|---------:|
|id|[long](../types/long.md) | Required|
|access\_hash|[long](../types/long.md) | Required|
|date|[int](../types/int.md) | Required|
|duration|[int](../types/int.md) | Required|
|mime\_type|[string](../types/string.md) | Required|
|size|[int](../types/int.md) | Required|
|thumb|[PhotoSize](../types/PhotoSize.md) | Required|
|dc\_id|[int](../types/int.md) | Required|
|w|[int](../types/int.md) | Required|
|h|[int](../types/int.md) | Required|



### Type: [Video](../types/Video.md)


### Example:

```
$video = ['_' => 'video', 'id' => long, 'access_hash' => long, 'date' => int, 'duration' => int, 'mime_type' => string, 'size' => int, 'thumb' => PhotoSize, 'dc_id' => int, 'w' => int, 'h' => int, ];
```  

