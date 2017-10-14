---
title: maskCoords
description: maskCoords attributes, type and example
---
## Constructor: maskCoords  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|---------------|----------|
|n|[int](../types/int.md) | Yes|
|x|[double](../types/double.md) | Yes|
|y|[double](../types/double.md) | Yes|
|zoom|[double](../types/double.md) | Yes|



### Type: [MaskCoords](../types/MaskCoords.md)


### Example:

```
$maskCoords = ['_' => 'maskCoords', 'n' => int, 'x' => double, 'y' => double, 'zoom' => double];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "maskCoords", "n": int, "x": double, "y": double, "zoom": double}
```


Or, if you're into Lua:  


```
maskCoords={_='maskCoords', n=int, x=double, y=double, zoom=double}

```


