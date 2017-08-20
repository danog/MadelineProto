---
title: inputGeoPoint
description: inputGeoPoint attributes, type and example
---
## Constructor: inputGeoPoint  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|---------------|----------|
|latitude|[double](../types/double.md) | Yes|
|longitude|[double](../types/double.md) | Yes|



### Type: [InputGeoPoint](../types/InputGeoPoint.md)


### Example:

```
$inputGeoPoint = ['_' => 'inputGeoPoint', 'latitude' => double, 'longitude' => double];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "inputGeoPoint", "latitude": double, "longitude": double}
```


Or, if you're into Lua:  


```
inputGeoPoint={_='inputGeoPoint', latitude=double, longitude=double}

```


