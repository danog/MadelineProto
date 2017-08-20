---
title: fileLocationUnavailable
description: fileLocationUnavailable attributes, type and example
---
## Constructor: fileLocationUnavailable  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|---------------|----------|
|volume\_id|[long](../types/long.md) | Yes|
|local\_id|[int](../types/int.md) | Yes|
|secret|[long](../types/long.md) | Yes|



### Type: [FileLocation](../types/FileLocation.md)


### Example:

```
$fileLocationUnavailable = ['_' => 'fileLocationUnavailable', 'volume_id' => long, 'local_id' => int, 'secret' => long];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "fileLocationUnavailable", "volume_id": long, "local_id": int, "secret": long}
```


Or, if you're into Lua:  


```
fileLocationUnavailable={_='fileLocationUnavailable', volume_id=long, local_id=int, secret=long}

```


