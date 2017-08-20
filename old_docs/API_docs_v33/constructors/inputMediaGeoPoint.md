---
title: inputMediaGeoPoint
description: inputMediaGeoPoint attributes, type and example
---
## Constructor: inputMediaGeoPoint  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|---------------|----------|
|geo\_point|[InputGeoPoint](../types/InputGeoPoint.md) | Yes|



### Type: [InputMedia](../types/InputMedia.md)


### Example:

```
$inputMediaGeoPoint = ['_' => 'inputMediaGeoPoint', 'geo_point' => InputGeoPoint];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "inputMediaGeoPoint", "geo_point": InputGeoPoint}
```


Or, if you're into Lua:  


```
inputMediaGeoPoint={_='inputMediaGeoPoint', geo_point=InputGeoPoint}

```


