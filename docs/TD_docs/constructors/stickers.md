---
title: stickers
description: Represents list of stickers
---
## Constructor: stickers  
[Back to constructors index](index.md)



Represents list of stickers

### Attributes:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|stickers|Array of [sticker](../constructors/sticker.md) | Yes|Stickers|



### Type: [Stickers](../types/Stickers.md)


### Example:

```
$stickers = ['_' => 'stickers', 'stickers' => [sticker]];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "stickers", "stickers": [sticker]}
```


Or, if you're into Lua:  


```
stickers={_='stickers', stickers={sticker}}

```


