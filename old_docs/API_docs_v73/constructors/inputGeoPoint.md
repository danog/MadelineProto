---
title: inputGeoPoint
description: inputGeoPoint attributes, type and example
---
## Constructor: inputGeoPoint  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|---------------|----------|
|lat|[double](../types/double.md) | Yes|
|long|[double](../types/double.md) | Yes|



### Type: [InputGeoPoint](../types/InputGeoPoint.md)


### Example:

```
$inputGeoPoint = ['_' => 'inputGeoPoint', 'lat' => double, 'long' => double];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "inputGeoPoint", "lat": double, "long": double}
```


Or, if you're into Lua:  


```
inputGeoPoint={_='inputGeoPoint', lat=double, long=double}

```


