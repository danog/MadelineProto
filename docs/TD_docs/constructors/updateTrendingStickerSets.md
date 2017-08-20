---
title: updateTrendingStickerSets
description: List of trending sticker sets was updated or some of them was viewed
---
## Constructor: updateTrendingStickerSets  
[Back to constructors index](index.md)



List of trending sticker sets was updated or some of them was viewed

### Attributes:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|sticker\_sets|[stickerSets](../types/stickerSets.md) | Yes|New list of trending sticker sets|



### Type: [Update](../types/Update.md)


### Example:

```
$updateTrendingStickerSets = ['_' => 'updateTrendingStickerSets', 'sticker_sets' => stickerSets];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "updateTrendingStickerSets", "sticker_sets": stickerSets}
```


Or, if you're into Lua:  


```
updateTrendingStickerSets={_='updateTrendingStickerSets', sticker_sets=stickerSets}

```


