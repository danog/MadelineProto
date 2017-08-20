---
title: inputFileLocation
description: inputFileLocation attributes, type and example
---
## Constructor: inputFileLocation  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|---------------|----------|
|volume\_id|[long](../types/long.md) | Yes|
|local\_id|[int](../types/int.md) | Yes|
|secret|[long](../types/long.md) | Yes|



### Type: [InputFileLocation](../types/InputFileLocation.md)


### Example:

```
$inputFileLocation = ['_' => 'inputFileLocation', 'volume_id' => long, 'local_id' => int, 'secret' => long];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "inputFileLocation", "volume_id": long, "local_id": int, "secret": long}
```


Or, if you're into Lua:  


```
inputFileLocation={_='inputFileLocation', volume_id=long, local_id=int, secret=long}

```


