---
title: audio
description: audio attributes, type and example
---
## Constructor: audio  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|---------------|----------|
|id|[long](../types/long.md) | Yes|
|access\_hash|[long](../types/long.md) | Yes|
|user\_id|[int](../types/int.md) | Yes|
|date|[int](../types/int.md) | Yes|
|duration|[int](../types/int.md) | Yes|
|mime\_type|[string](../types/string.md) | Yes|
|size|[int](../types/int.md) | Yes|
|dc\_id|[int](../types/int.md) | Yes|



### Type: [Audio](../types/Audio.md)


### Example:

```
$audio = ['_' => 'audio', 'id' => long, 'access_hash' => long, 'user_id' => int, 'date' => int, 'duration' => int, 'mime_type' => 'string', 'size' => int, 'dc_id' => int];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "audio", "id": long, "access_hash": long, "user_id": int, "date": int, "duration": int, "mime_type": "string", "size": int, "dc_id": int}
```


Or, if you're into Lua:  


```
audio={_='audio', id=long, access_hash=long, user_id=int, date=int, duration=int, mime_type='string', size=int, dc_id=int}

```


