---
title: stickerSet
description: Represents sticker set
---
## Constructor: stickerSet  
[Back to constructors index](index.md)



Represents sticker set

### Attributes:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|id|[long](../types/long.md) | Yes|Sticker set identifier|
|title|[string](../types/string.md) | Yes|Title of the sticker set|
|name|[string](../types/string.md) | Yes|Name of the sticker set|
|is\_installed|[Bool](../types/Bool.md) | Yes|True if sticker set is installed by logged in user|
|is\_archived|[Bool](../types/Bool.md) | Yes|True if sticker set is archived. A sticker set can't be installed and archived simultaneously|
|is\_official|[Bool](../types/Bool.md) | Yes|True if sticker set is official|
|is\_masks|[Bool](../types/Bool.md) | Yes|True if stickers in the set are masks|
|is\_viewed|[Bool](../types/Bool.md) | Yes|True for viewed trending sticker set|
|stickers|Array of [sticker](../constructors/sticker.md) | Yes|List of stickers in this set|
|emojis|Array of [stickerEmojis](../constructors/stickerEmojis.md) | Yes|Lists of emojis corresponding to the stickers in the same order|



### Type: [StickerSet](../types/StickerSet.md)


