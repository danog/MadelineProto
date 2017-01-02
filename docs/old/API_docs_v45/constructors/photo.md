---
title: photo
description: photo attributes, type and example
---
## Constructor: photo  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|:-------------:|---------:|
|id|[long](../types/long.md) | Required|
|access\_hash|[long](../types/long.md) | Required|
|date|[int](../types/int.md) | Required|
|sizes|Array of [PhotoSize](../types/PhotoSize.md) | Required|



### Type: [Photo](../types/Photo.md)


### Example:

```
$photo = ['_' => 'photo', 'id' => long, 'access_hash' => long, 'date' => int, 'sizes' => [Vector t], ];
```  

