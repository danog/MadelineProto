---
title: createNewStickerSet
description: Bots only. Creates new sticker set. Returns created sticker set
---
## Method: createNewStickerSet  
[Back to methods index](index.md)


YOU CANNOT USE THIS METHOD IN MADELINEPROTO


Bots only. Creates new sticker set. Returns created sticker set

### Params:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|user\_id|[int](../types/int.md) | Yes|Sticker set owner|
|title|[string](../types/string.md) | Yes|Sticker set title, 1-64 characters|
|name|[string](../types/string.md) | Yes|Sticker set name. Can contain only english letters, digits and underscores. Should end on *"_by_<bot username>"*. *<bot_username>* is case insensitive, 1-64 characters|
|is\_masks|[Bool](../types/Bool.md) | Yes|True, is stickers are masks|
|stickers|Array of [inputSticker](../types/inputSticker.md) | Yes|List of stickers to add to the set|


### Return type: [StickerSet](../types/StickerSet.md)

