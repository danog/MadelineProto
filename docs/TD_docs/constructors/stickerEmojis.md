---
title: stickerEmojis
description: Represents list of all emojis corresponding to a sticker in a sticker set. The list is only for informational purposes because sticker is always sent with a fixed emoji from the corresponding Sticker object
---
## Constructor: stickerEmojis  
[Back to constructors index](index.md)



Represents list of all emojis corresponding to a sticker in a sticker set. The list is only for informational purposes because sticker is always sent with a fixed emoji from the corresponding Sticker object

### Attributes:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|emojis|Array of [string](../constructors/string.md) | Yes|List of emojis|



### Type: [StickerEmojis](../types/StickerEmojis.md)


### Example:

```
$stickerEmojis = ['_' => 'stickerEmojis', 'emojis' => ['string']];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "stickerEmojis", "emojis": ["string"]}
```


Or, if you're into Lua:  


```
stickerEmojis={_='stickerEmojis', emojis={'string'}}

```


