---
title: messages.allStickers
description: messages_allStickers attributes, type and example
---
## Constructor: messages.allStickers  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|---------------|----------|
|hash|[string](../types/string.md) | Yes|
|packs|Array of [StickerPack](../types/StickerPack.md) | Yes|
|documents|Array of [Document](../types/Document.md) | Yes|



### Type: [messages\_AllStickers](../types/messages_AllStickers.md)


### Example:

```
$messages_allStickers = ['_' => 'messages.allStickers', 'hash' => 'string', 'packs' => [StickerPack], 'documents' => [Document]];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "messages.allStickers", "hash": "string", "packs": [StickerPack], "documents": [Document]}
```


Or, if you're into Lua:  


```
messages_allStickers={_='messages.allStickers', hash='string', packs={StickerPack}, documents={Document}}

```


