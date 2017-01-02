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
|user\_id|[int](../types/int.md) | Required|
|date|[int](../types/int.md) | Required|
|caption|[string](../types/string.md) | Required|
|geo|[GeoPoint](../types/GeoPoint.md) | Required|
|sizes|Array of [PhotoSize](../types/PhotoSize.md) | Required|



### Type: [Photo](../types/Photo.md)


### Example:

```
$photo = ['_' => 'photo', 'id' => long, 'access_hash' => long, 'user_id' => int, 'date' => int, 'caption' => string, 'geo' => GeoPoint, 'sizes' => [Vector t], ];
```  

