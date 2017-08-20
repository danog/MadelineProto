---
title: updateStickerSets
description: List of installed sticker sets was updated
---
## Constructor: updateStickerSets  
[Back to constructors index](index.md)



List of installed sticker sets was updated

### Attributes:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|is\_masks|[Bool](../types/Bool.md) | Yes|True, if list of installed mask sticker sets was updated|
|sticker\_set\_ids|Array of [long](../constructors/long.md) | Yes|New list of installed sticker sets|



### Type: [Update](../types/Update.md)


### Example:

```
$updateStickerSets = ['_' => 'updateStickerSets', 'is_masks' => Bool, 'sticker_set_ids' => [long]];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "updateStickerSets", "is_masks": Bool, "sticker_set_ids": [long]}
```


Or, if you're into Lua:  


```
updateStickerSets={_='updateStickerSets', is_masks=Bool, sticker_set_ids={long}}

```


