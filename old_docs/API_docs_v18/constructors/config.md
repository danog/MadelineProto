---
title: config
description: config attributes, type and example
---
## Constructor: config  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|:-------------:|---------:|
|date|[int](../types/int.md) | Required|
|test\_mode|[Bool](../types/Bool.md) | Required|
|this\_dc|[int](../types/int.md) | Required|
|dc\_options|Array of [DcOption](../types/DcOption.md) | Required|
|chat\_size\_max|[int](../types/int.md) | Required|
|broadcast\_size\_max|[int](../types/int.md) | Required|



### Type: [Config](../types/Config.md)


### Example:

```
$config = ['_' => 'config', 'date' => int, 'test_mode' => Bool, 'this_dc' => int, 'dc_options' => [Vector t], 'chat_size_max' => int, 'broadcast_size_max' => int, ];
```  

