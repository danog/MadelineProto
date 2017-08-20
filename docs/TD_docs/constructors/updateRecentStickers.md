---
title: updateRecentStickers
description: List of recently used stickers was updated
---
## Constructor: updateRecentStickers  
[Back to constructors index](index.md)



List of recently used stickers was updated

### Attributes:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|is\_attached|[Bool](../types/Bool.md) | Yes|True, if the list of stickers attached to photo or video files was updated, otherwise the list of sent stickers is updated|
|sticker\_ids|Array of [int](../constructors/int.md) | Yes|New list of recently used stickers|



### Type: [Update](../types/Update.md)


### Example:

```
$updateRecentStickers = ['_' => 'updateRecentStickers', 'is_attached' => Bool, 'sticker_ids' => [int]];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "updateRecentStickers", "is_attached": Bool, "sticker_ids": [int]}
```


Or, if you're into Lua:  


```
updateRecentStickers={_='updateRecentStickers', is_attached=Bool, sticker_ids={int}}

```


