---
title: inputMediaVenue
description: inputMediaVenue attributes, type and example
---
## Constructor: inputMediaVenue  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|---------------|----------|
|geo\_point|[InputGeoPoint](../types/InputGeoPoint.md) | Yes|
|title|[string](../types/string.md) | Yes|
|address|[string](../types/string.md) | Yes|
|provider|[string](../types/string.md) | Yes|
|venue\_id|[string](../types/string.md) | Yes|



### Type: [InputMedia](../types/InputMedia.md)


### Example:

```
$inputMediaVenue = ['_' => 'inputMediaVenue', 'geo_point' => InputGeoPoint, 'title' => 'string', 'address' => 'string', 'provider' => 'string', 'venue_id' => 'string'];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "inputMediaVenue", "geo_point": InputGeoPoint, "title": "string", "address": "string", "provider": "string", "venue_id": "string"}
```


Or, if you're into Lua:  


```
inputMediaVenue={_='inputMediaVenue', geo_point=InputGeoPoint, title='string', address='string', provider='string', venue_id='string'}

```


