---
title: geoPoint
description: geoPoint attributes, type and example
---
## Constructor: geoPoint  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|---------------|----------|
|longitude|[double](../types/double.md) | Yes|
|latitude|[double](../types/double.md) | Yes|



### Type: [GeoPoint](../types/GeoPoint.md)


### Example:

```
$geoPoint = ['_' => 'geoPoint', 'longitude' => double, 'latitude' => double];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "geoPoint", "longitude": double, "latitude": double}
```


Or, if you're into Lua:  


```
geoPoint={_='geoPoint', longitude=double, latitude=double}

```


