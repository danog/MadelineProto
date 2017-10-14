---
title: messages.featuredStickers
description: messages_featuredStickers attributes, type and example
---
## Constructor: messages.featuredStickers  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|---------------|----------|
|hash|[int](../types/int.md) | Yes|
|sets|Array of [StickerSetCovered](../types/StickerSetCovered.md) | Yes|
|unread|Array of [long](../types/long.md) | Yes|



### Type: [messages\_FeaturedStickers](../types/messages_FeaturedStickers.md)


### Example:

```
$messages_featuredStickers = ['_' => 'messages.featuredStickers', 'hash' => int, 'sets' => [StickerSetCovered], 'unread' => [long]];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "messages.featuredStickers", "hash": int, "sets": [StickerSetCovered], "unread": [long]}
```


Or, if you're into Lua:  


```
messages_featuredStickers={_='messages.featuredStickers', hash=int, sets={StickerSetCovered}, unread={long}}

```


