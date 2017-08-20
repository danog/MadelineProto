---
title: stickerSetMultiCovered
description: stickerSetMultiCovered attributes, type and example
---
## Constructor: stickerSetMultiCovered  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|---------------|----------|
|set|[StickerSet](../types/StickerSet.md) | Yes|
|covers|Array of [Document](../types/Document.md) | Yes|



### Type: [StickerSetCovered](../types/StickerSetCovered.md)


### Example:

```
$stickerSetMultiCovered = ['_' => 'stickerSetMultiCovered', 'set' => StickerSet, 'covers' => [Document]];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "stickerSetMultiCovered", "set": StickerSet, "covers": [Document]}
```


Or, if you're into Lua:  


```
stickerSetMultiCovered={_='stickerSetMultiCovered', set=StickerSet, covers={Document}}

```


