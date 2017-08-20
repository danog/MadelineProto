---
title: documentAttributeSticker
description: documentAttributeSticker attributes, type and example
---
## Constructor: documentAttributeSticker  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|---------------|----------|
|alt|[string](../types/string.md) | Yes|
|stickerset|[InputStickerSet](../types/InputStickerSet.md) | Yes|



### Type: [DocumentAttribute](../types/DocumentAttribute.md)


### Example:

```
$documentAttributeSticker = ['_' => 'documentAttributeSticker', 'alt' => 'string', 'stickerset' => InputStickerSet];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "documentAttributeSticker", "alt": "string", "stickerset": InputStickerSet}
```


Or, if you're into Lua:  


```
documentAttributeSticker={_='documentAttributeSticker', alt='string', stickerset=InputStickerSet}

```


