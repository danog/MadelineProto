---
title: fileLocation23
description: fileLocation23 attributes, type and example
---
## Constructor: fileLocation23  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|:-------------:|---------:|
|dc\_id|[int](../types/int.md) | Yes|
|volume\_id|[long](../types/long.md) | Yes|
|local\_id|[int](../types/int.md) | Yes|
|secret|[long](../types/long.md) | Yes|



### Type: [FileLocation](../types/FileLocation.md)


### Example:

```
$fileLocation23 = ['_' => 'fileLocation23', 'dc_id' => int, 'volume_id' => long, 'local_id' => int, 'secret' => long, ];
```  

Or, if you're into Lua:  


```
fileLocation23={_='fileLocation23', dc_id=int, volume_id=long, local_id=int, secret=long, }

```


