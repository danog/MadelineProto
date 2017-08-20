---
title: messageMediaVenue
description: messageMediaVenue attributes, type and example
---
## Constructor: messageMediaVenue  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|---------------|----------|
|geo|[GeoPoint](../types/GeoPoint.md) | Yes|
|title|[string](../types/string.md) | Yes|
|address|[string](../types/string.md) | Yes|
|provider|[string](../types/string.md) | Yes|
|venue\_id|[string](../types/string.md) | Yes|



### Type: [MessageMedia](../types/MessageMedia.md)


### Example:

```
$messageMediaVenue = ['_' => 'messageMediaVenue', 'geo' => GeoPoint, 'title' => 'string', 'address' => 'string', 'provider' => 'string', 'venue_id' => 'string'];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "messageMediaVenue", "geo": GeoPoint, "title": "string", "address": "string", "provider": "string", "venue_id": "string"}
```


Or, if you're into Lua:  


```
messageMediaVenue={_='messageMediaVenue', geo=GeoPoint, title='string', address='string', provider='string', venue_id='string'}

```


