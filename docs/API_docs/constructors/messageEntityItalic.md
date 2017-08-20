---
title: messageEntityItalic
description: Italic text
---
## Constructor: messageEntityItalic  
[Back to constructors index](index.md)



Italic text

### Attributes:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|offset|[int](../types/int.md) | Yes|Offset of the entity in UTF-16 code points|
|length|[int](../types/int.md) | Yes|Length of the entity in UTF-16 code points|



### Type: [MessageEntity](../types/MessageEntity.md)


### Example:

```
$messageEntityItalic = ['_' => 'messageEntityItalic', 'offset' => int, 'length' => int];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "messageEntityItalic", "offset": int, "length": int}
```


Or, if you're into Lua:  


```
messageEntityItalic={_='messageEntityItalic', offset=int, length=int}

```


