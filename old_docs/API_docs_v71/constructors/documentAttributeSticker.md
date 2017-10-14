---
title: documentAttributeSticker
description: documentAttributeSticker attributes, type and example
---
## Constructor: documentAttributeSticker  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|---------------|----------|
|mask|[Bool](../types/Bool.md) | Optional|
|alt|[string](../types/string.md) | Yes|
|stickerset|[InputStickerSet](../types/InputStickerSet.md) | Yes|
|mask\_coords|[MaskCoords](../types/MaskCoords.md) | Optional|



### Type: [DocumentAttribute](../types/DocumentAttribute.md)


### Example:

```
$documentAttributeSticker = ['_' => 'documentAttributeSticker', 'mask' => Bool, 'alt' => 'string', 'stickerset' => InputStickerSet, 'mask_coords' => MaskCoords];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "documentAttributeSticker", "mask": Bool, "alt": "string", "stickerset": InputStickerSet, "mask_coords": MaskCoords}
```


Or, if you're into Lua:  


```
documentAttributeSticker={_='documentAttributeSticker', mask=Bool, alt='string', stickerset=InputStickerSet, mask_coords=MaskCoords}

```


