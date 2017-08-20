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
|sets|Array of [StickerSet](../types/StickerSet.md) | Yes|



### Type: [messages\_AllStickers](../types/messages_AllStickers.md)


### Example:

```
$messages_allStickers = ['_' => 'messages.allStickers', 'hash' => 'string', 'sets' => [StickerSet]];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "messages.allStickers", "hash": "string", "sets": [StickerSet]}
```


Or, if you're into Lua:  


```
messages_allStickers={_='messages.allStickers', hash='string', sets={StickerSet}}

```


