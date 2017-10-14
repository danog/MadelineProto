---
title: messages.stickerSet
description: messages_stickerSet attributes, type and example
---
## Constructor: messages.stickerSet  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|---------------|----------|
|set|[StickerSet](../types/StickerSet.md) | Yes|
|packs|Array of [StickerPack](../types/StickerPack.md) | Yes|
|documents|Array of [Document](../types/Document.md) | Yes|



### Type: [messages\_StickerSet](../types/messages_StickerSet.md)


### Example:

```
$messages_stickerSet = ['_' => 'messages.stickerSet', 'set' => StickerSet, 'packs' => [StickerPack], 'documents' => [Document]];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "messages.stickerSet", "set": StickerSet, "packs": [StickerPack], "documents": [Document]}
```


Or, if you're into Lua:  


```
messages_stickerSet={_='messages.stickerSet', set=StickerSet, packs={StickerPack}, documents={Document}}

```


