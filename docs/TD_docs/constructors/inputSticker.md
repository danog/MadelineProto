---
title: inputSticker
description: Description of a sticker which should be added to a sticker set
---
## Constructor: inputSticker  
[Back to constructors index](index.md)



Description of a sticker which should be added to a sticker set

### Attributes:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|png\_sticker|[InputFile](../types/InputFile.md) | Yes|Png image with the sticker, must be up to 512 kilobytes in size and fit in 512x512 square|
|emojis|[string](../types/string.md) | Yes|Emojis corresponding to the sticker|
|mask\_position|[maskPosition](../types/maskPosition.md) | Yes|Position where the mask should be placed, nullable|



### Type: [InputSticker](../types/InputSticker.md)


