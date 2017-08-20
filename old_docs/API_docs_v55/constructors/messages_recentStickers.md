---
title: messages.recentStickers
description: messages_recentStickers attributes, type and example
---
## Constructor: messages.recentStickers  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|---------------|----------|
|hash|[int](../types/int.md) | Yes|
|stickers|Array of [Document](../types/Document.md) | Yes|



### Type: [messages\_RecentStickers](../types/messages_RecentStickers.md)


### Example:

```
$messages_recentStickers = ['_' => 'messages.recentStickers', 'hash' => int, 'stickers' => [Document]];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "messages.recentStickers", "hash": int, "stickers": [Document]}
```


Or, if you're into Lua:  


```
messages_recentStickers={_='messages.recentStickers', hash=int, stickers={Document}}

```


