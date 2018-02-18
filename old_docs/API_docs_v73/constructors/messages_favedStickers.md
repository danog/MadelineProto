---
title: messages.favedStickers
description: messages_favedStickers attributes, type and example
---
## Constructor: messages.favedStickers  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|---------------|----------|
|hash|[int](../types/int.md) | Yes|
|packs|Array of [StickerPack](../types/StickerPack.md) | Yes|
|stickers|Array of [Document](../types/Document.md) | Yes|



### Type: [messages\_FavedStickers](../types/messages_FavedStickers.md)


### Example:

```
$messages_favedStickers = ['_' => 'messages.favedStickers', 'hash' => int, 'packs' => [StickerPack], 'stickers' => [Document]];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "messages.favedStickers", "hash": int, "packs": [StickerPack], "stickers": [Document]}
```


Or, if you're into Lua:  


```
messages_favedStickers={_='messages.favedStickers', hash=int, packs={StickerPack}, stickers={Document}}

```


