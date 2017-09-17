---
title: messageEntityItalic
description: messageEntityItalic attributes, type and example
---
## Constructor: messageEntityItalic  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|---------------|----------|
|offset|[int](../types/int.md) | Yes|
|length|[int](../types/int.md) | Yes|



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


