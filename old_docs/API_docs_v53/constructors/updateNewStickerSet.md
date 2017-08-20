---
title: updateNewStickerSet
description: updateNewStickerSet attributes, type and example
---
## Constructor: updateNewStickerSet  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|---------------|----------|
|stickerset|[messages\_StickerSet](../types/messages_StickerSet.md) | Yes|



### Type: [Update](../types/Update.md)


### Example:

```
$updateNewStickerSet = ['_' => 'updateNewStickerSet', 'stickerset' => messages_StickerSet];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "updateNewStickerSet", "stickerset": messages_StickerSet}
```


Or, if you're into Lua:  


```
updateNewStickerSet={_='updateNewStickerSet', stickerset=messages_StickerSet}

```


