---
title: inlineQueryResultSticker
description: Represents a sticker cached on the telegram server
---
## Constructor: inlineQueryResultSticker  
[Back to constructors index](index.md)



Represents a sticker cached on the telegram server

### Attributes:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|id|[string](../types/string.md) | Yes|Unique identifier of this result|
|sticker|[sticker](../types/sticker.md) | Yes|The sticker|



### Type: [InlineQueryResult](../types/InlineQueryResult.md)


### Example:

```
$inlineQueryResultSticker = ['_' => 'inlineQueryResultSticker', 'id' => 'string', 'sticker' => sticker];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "inlineQueryResultSticker", "id": "string", "sticker": sticker}
```


Or, if you're into Lua:  


```
inlineQueryResultSticker={_='inlineQueryResultSticker', id='string', sticker=sticker}

```


