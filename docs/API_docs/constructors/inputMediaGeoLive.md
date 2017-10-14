---
title: inputMediaGeoLive
description: inputMediaGeoLive attributes, type and example
---
## Constructor: inputMediaGeoLive  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|---------------|----------|
|geo\_point|[InputGeoPoint](../types/InputGeoPoint.md) | Yes|
|period|[int](../types/int.md) | Yes|



### Type: [InputMedia](../types/InputMedia.md)


### Example:

```
$inputMediaGeoLive = ['_' => 'inputMediaGeoLive', 'geo_point' => InputGeoPoint, 'period' => int];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "inputMediaGeoLive", "geo_point": InputGeoPoint, "period": int}
```


Or, if you're into Lua:  


```
inputMediaGeoLive={_='inputMediaGeoLive', geo_point=InputGeoPoint, period=int}

```


