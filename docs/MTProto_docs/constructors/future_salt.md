---
title: future_salt
description: future_salt attributes, type and example
---
## Constructor: future\_salt  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|:-------------:|---------:|
|valid\_since|[int](../types/int.md) | Required|
|valid\_until|[int](../types/int.md) | Required|
|salt|[long](../types/long.md) | Required|



### Type: [FutureSalt](../types/FutureSalt.md)


### Example:

```
$future_salt = ['_' => 'future_salt', 'valid_since' => int, 'valid_until' => int, 'salt' => long, ];
```  

