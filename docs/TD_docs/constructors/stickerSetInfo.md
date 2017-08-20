---
title: stickerSetInfo
description: Represents short information about sticker set
---
## Constructor: stickerSetInfo  
[Back to constructors index](index.md)



Represents short information about sticker set

### Attributes:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|id|[long](../types/long.md) | Yes|Sticker set identifier|
|title|[string](../types/string.md) | Yes|Title of the sticker set|
|name|[string](../types/string.md) | Yes|Name of the sticker set|
|is\_installed|[Bool](../types/Bool.md) | Yes|True if sticker set is installed by logged in user|
|is\_archived|[Bool](../types/Bool.md) | Yes|True if sticker set is archived|
|is\_official|[Bool](../types/Bool.md) | Yes|True if sticker set is official|
|is\_masks|[Bool](../types/Bool.md) | Yes|True if stickers in the set are masks|
|is\_viewed|[Bool](../types/Bool.md) | Yes|True for viewed trending sticker set|
|size|[int](../types/int.md) | Yes|Total number of stickers in the set|
|covers|Array of [sticker](../constructors/sticker.md) | Yes|Up to 5 first stickers from the set depending on the context|



### Type: [StickerSetInfo](../types/StickerSetInfo.md)


### Example:

```
$stickerSetInfo = ['_' => 'stickerSetInfo', 'id' => long, 'title' => 'string', 'name' => 'string', 'is_installed' => Bool, 'is_archived' => Bool, 'is_official' => Bool, 'is_masks' => Bool, 'is_viewed' => Bool, 'size' => int, 'covers' => [sticker]];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "stickerSetInfo", "id": long, "title": "string", "name": "string", "is_installed": Bool, "is_archived": Bool, "is_official": Bool, "is_masks": Bool, "is_viewed": Bool, "size": int, "covers": [sticker]}
```


Or, if you're into Lua:  


```
stickerSetInfo={_='stickerSetInfo', id=long, title='string', name='string', is_installed=Bool, is_archived=Bool, is_official=Bool, is_masks=Bool, is_viewed=Bool, size=int, covers={sticker}}

```


