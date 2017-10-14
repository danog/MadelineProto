---
title: messageMediaGeoLive
description: messageMediaGeoLive attributes, type and example
---
## Constructor: messageMediaGeoLive  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|---------------|----------|
|geo|[GeoPoint](../types/GeoPoint.md) | Yes|
|period|[int](../types/int.md) | Yes|



### Type: [MessageMedia](../types/MessageMedia.md)


### Example:

```
$messageMediaGeoLive = ['_' => 'messageMediaGeoLive', 'geo' => GeoPoint, 'period' => int];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "messageMediaGeoLive", "geo": GeoPoint, "period": int}
```


Or, if you're into Lua:  


```
messageMediaGeoLive={_='messageMediaGeoLive', geo=GeoPoint, period=int}

```


