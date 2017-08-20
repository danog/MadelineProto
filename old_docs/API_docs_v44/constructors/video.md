---
title: video
description: video attributes, type and example
---
## Constructor: video  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|---------------|----------|
|id|[long](../types/long.md) | Yes|
|access\_hash|[long](../types/long.md) | Yes|
|date|[int](../types/int.md) | Yes|
|duration|[int](../types/int.md) | Yes|
|mime\_type|[string](../types/string.md) | Yes|
|size|[int](../types/int.md) | Yes|
|thumb|[PhotoSize](../types/PhotoSize.md) | Yes|
|dc\_id|[int](../types/int.md) | Yes|
|w|[int](../types/int.md) | Yes|
|h|[int](../types/int.md) | Yes|



### Type: [Video](../types/Video.md)


### Example:

```
$video = ['_' => 'video', 'id' => long, 'access_hash' => long, 'date' => int, 'duration' => int, 'mime_type' => 'string', 'size' => int, 'thumb' => PhotoSize, 'dc_id' => int, 'w' => int, 'h' => int];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "video", "id": long, "access_hash": long, "date": int, "duration": int, "mime_type": "string", "size": int, "thumb": PhotoSize, "dc_id": int, "w": int, "h": int}
```


Or, if you're into Lua:  


```
video={_='video', id=long, access_hash=long, date=int, duration=int, mime_type='string', size=int, thumb=PhotoSize, dc_id=int, w=int, h=int}

```


