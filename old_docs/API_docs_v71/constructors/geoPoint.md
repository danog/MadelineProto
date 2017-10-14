---
title: geoPoint
description: geoPoint attributes, type and example
---
## Constructor: geoPoint  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|---------------|----------|
|long|[double](../types/double.md) | Yes|
|lat|[double](../types/double.md) | Yes|



### Type: [GeoPoint](../types/GeoPoint.md)


### Example:

```
$geoPoint = ['_' => 'geoPoint', 'long' => double, 'lat' => double];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "geoPoint", "long": double, "lat": double}
```


Or, if you're into Lua:  


```
geoPoint={_='geoPoint', long=double, lat=double}

```


