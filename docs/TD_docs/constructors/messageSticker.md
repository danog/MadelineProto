---
title: messageSticker
description: Sticker message
---
## Constructor: messageSticker  
[Back to constructors index](index.md)



Sticker message

### Attributes:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|sticker|[sticker](../types/sticker.md) | Yes|Message content|



### Type: [MessageContent](../types/MessageContent.md)


### Example:

```
$messageSticker = ['_' => 'messageSticker', 'sticker' => sticker];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "messageSticker", "sticker": sticker}
```


Or, if you're into Lua:  


```
messageSticker={_='messageSticker', sticker=sticker}

```


