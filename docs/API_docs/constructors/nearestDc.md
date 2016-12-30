---
title: nearestDc
description: nearestDc attributes, type and example
---
## Constructor: nearestDc  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|:-------------:|---------:|
|country|[string](../types/string.md) | Required|
|this\_dc|[int](../types/int.md) | Required|
|nearest\_dc|[int](../types/int.md) | Required|



### Type: [NearestDc](../types/NearestDc.md)


### Example:

```
$nearestDc = ['_' => nearestDc, 'country' => string, 'this_dc' => int, 'nearest_dc' => int, ];
```