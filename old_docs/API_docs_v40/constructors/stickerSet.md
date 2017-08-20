---
title: stickerSet
description: stickerSet attributes, type and example
---
## Constructor: stickerSet  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|---------------|----------|
|id|[long](../types/long.md) | Yes|
|access\_hash|[long](../types/long.md) | Yes|
|title|[string](../types/string.md) | Yes|
|short\_name|[string](../types/string.md) | Yes|
|count|[int](../types/int.md) | Yes|
|hash|[int](../types/int.md) | Yes|



### Type: [StickerSet](../types/StickerSet.md)


### Example:

```
$stickerSet = ['_' => 'stickerSet', 'id' => long, 'access_hash' => long, 'title' => 'string', 'short_name' => 'string', 'count' => int, 'hash' => int];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "stickerSet", "id": long, "access_hash": long, "title": "string", "short_name": "string", "count": int, "hash": int}
```


Or, if you're into Lua:  


```
stickerSet={_='stickerSet', id=long, access_hash=long, title='string', short_name='string', count=int, hash=int}

```


