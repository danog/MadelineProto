---
title: geoChat
description: geoChat attributes, type and example
---
## Constructor: geoChat  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|---------------|----------|
|id|[int](../types/int.md) | Yes|
|access\_hash|[long](../types/long.md) | Yes|
|title|[string](../types/string.md) | Yes|
|address|[string](../types/string.md) | Yes|
|venue|[string](../types/string.md) | Yes|
|geo|[GeoPoint](../types/GeoPoint.md) | Yes|
|photo|[ChatPhoto](../types/ChatPhoto.md) | Yes|
|participants\_count|[int](../types/int.md) | Yes|
|date|[int](../types/int.md) | Yes|
|checked\_in|[Bool](../types/Bool.md) | Yes|
|version|[int](../types/int.md) | Yes|



### Type: [Chat](../types/Chat.md)


### Example:

```
$geoChat = ['_' => 'geoChat', 'id' => int, 'access_hash' => long, 'title' => 'string', 'address' => 'string', 'venue' => 'string', 'geo' => GeoPoint, 'photo' => ChatPhoto, 'participants_count' => int, 'date' => int, 'checked_in' => Bool, 'version' => int];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "geoChat", "id": int, "access_hash": long, "title": "string", "address": "string", "venue": "string", "geo": GeoPoint, "photo": ChatPhoto, "participants_count": int, "date": int, "checked_in": Bool, "version": int}
```


Or, if you're into Lua:  


```
geoChat={_='geoChat', id=int, access_hash=long, title='string', address='string', venue='string', geo=GeoPoint, photo=ChatPhoto, participants_count=int, date=int, checked_in=Bool, version=int}

```


