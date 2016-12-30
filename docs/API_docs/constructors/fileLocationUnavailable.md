---
title: fileLocationUnavailable
description: fileLocationUnavailable attributes, type and example
---
## Constructor: fileLocationUnavailable  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|:-------------:|---------:|
|volume\_id|[long](../types/long.md) | Required|
|local\_id|[int](../types/int.md) | Required|
|secret|[long](../types/long.md) | Required|



### Type: [FileLocation](../types/FileLocation.md)


### Example:

```
$fileLocationUnavailable = ['_' => 'fileLocationUnavailable', 'volume_id' => long, 'local_id' => int, 'secret' => long, ];
```