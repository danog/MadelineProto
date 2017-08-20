---
title: stickerSetCovered
description: stickerSetCovered attributes, type and example
---
## Constructor: stickerSetCovered  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|---------------|----------|
|set|[StickerSet](../types/StickerSet.md) | Yes|
|cover|[Document](../types/Document.md) | Yes|



### Type: [StickerSetCovered](../types/StickerSetCovered.md)


### Example:

```
$stickerSetCovered = ['_' => 'stickerSetCovered', 'set' => StickerSet, 'cover' => Document];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "stickerSetCovered", "set": StickerSet, "cover": Document}
```


Or, if you're into Lua:  


```
stickerSetCovered={_='stickerSetCovered', set=StickerSet, cover=Document}

```


