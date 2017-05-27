---
title: fileLocationUnavailable23
description: fileLocationUnavailable23 attributes, type and example
---
## Constructor: fileLocationUnavailable23  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|:-------------:|---------:|
|volume\_id|[long](../types/long.md) | Yes|
|local\_id|[int](../types/int.md) | Yes|
|secret|[long](../types/long.md) | Yes|



### Type: [FileLocation](../types/FileLocation.md)


### Example:

```
$fileLocationUnavailable23 = ['_' => 'fileLocationUnavailable23', 'volume_id' => long, 'local_id' => int, 'secret' => long, ];
```  

Or, if you're into Lua:  


```
fileLocationUnavailable23={_='fileLocationUnavailable23', volume_id=long, local_id=int, secret=long, }

```


