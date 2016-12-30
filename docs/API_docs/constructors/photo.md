---
title: photo
description: photo attributes, type and example
---
## Constructor: photo  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|:-------------:|---------:|
|has\_stickers|[Bool](../types/Bool.md) | Optional|
|id|[long](../types/long.md) | Required|
|access\_hash|[long](../types/long.md) | Required|
|date|[int](../types/int.md) | Required|
|sizes|Array of [PhotoSize](../types/PhotoSize.md) | Required|



### Type: [Photo](../types/Photo.md)


### Example:

```
$photo = ['_' => photo, 'has_stickers' => true, 'id' => long, 'access_hash' => long, 'date' => int, 'sizes' => [Vector t], ];
```