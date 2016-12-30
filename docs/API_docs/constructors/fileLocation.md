---
title: fileLocation
description: fileLocation attributes, type and example
---
## Constructor: fileLocation  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|:-------------:|---------:|
|dc\_id|[int](../types/int.md) | Required|
|volume\_id|[long](../types/long.md) | Required|
|local\_id|[int](../types/int.md) | Required|
|secret|[long](../types/long.md) | Required|



### Type: [FileLocation](../types/FileLocation.md)


### Example:

```
$fileLocation = ['_' => 'fileLocation', 'dc_id' => int, 'volume_id' => long, 'local_id' => int, 'secret' => long, ];
```