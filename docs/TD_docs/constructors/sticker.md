---
title: sticker
description: Describes sticker
---
## Constructor: sticker  
[Back to constructors index](index.md)



Describes sticker

### Attributes:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|set\_id|[long](../types/long.md) | Yes|Identifier of sticker set to which the sticker belongs or 0 if none|
|width|[int](../types/int.md) | Yes|Sticker width as defined by sender|
|height|[int](../types/int.md) | Yes|Sticker height as defined by sender|
|emoji|[string](../types/string.md) | Yes|Emoji corresponding to the sticker|
|is\_mask|[Bool](../types/Bool.md) | Yes|True, if the sticker is a mask|
|mask\_position|[maskPosition](../types/maskPosition.md) | Yes|Position where the mask should be placed, nullable|
|thumb|[photoSize](../types/photoSize.md) | Yes|Sticker thumb in webp or jpeg format, nullable|
|sticker|[file](../types/file.md) | Yes|File with sticker|



### Type: [Sticker](../types/Sticker.md)


