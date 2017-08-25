---
title: addRecentSticker
description: Manually adds new sticker to the list of recently used stickers. New sticker is added to the beginning of the list. If the sticker is already in the list, at first it is removed from the list
---
## Method: addRecentSticker  
[Back to methods index](index.md)


YOU CANNOT USE THIS METHOD IN MADELINEPROTO


Manually adds new sticker to the list of recently used stickers. New sticker is added to the beginning of the list. If the sticker is already in the list, at first it is removed from the list

### Params:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|is\_attached|[Bool](../types/Bool.md) | Yes|Pass true to add the sticker to the list of stickers recently attached to photo or video files, pass false to add the sticker to the list of recently sent stickers|
|sticker|[InputFile](../types/InputFile.md) | Yes|Sticker file to add|


### Return type: [Stickers](../types/Stickers.md)

