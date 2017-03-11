---
title: updateNewStickerSet
description: updateNewStickerSet attributes, type and example
---
## Constructor: updateNewStickerSet  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|:-------------:|---------:|
|stickerset|[messages\_StickerSet](../types/messages_StickerSet.md) | Yes|



### Type: [Update](../types/Update.md)


### Example:

```
$updateNewStickerSet = ['_' => 'updateNewStickerSet', 'stickerset' => messages.StickerSet, ];
```  

Or, if you're into Lua:  


```
updateNewStickerSet={_='updateNewStickerSet', stickerset=messages.StickerSet, }

```


