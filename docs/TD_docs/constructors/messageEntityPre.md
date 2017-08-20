---
title: messageEntityPre
description: Text needs to be formatted as inside of pre HTML tag
---
## Constructor: messageEntityPre  
[Back to constructors index](index.md)



Text needs to be formatted as inside of pre HTML tag

### Attributes:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|offset|[int](../types/int.md) | Yes|Offset of the entity in UTF-16 code points|
|length|[int](../types/int.md) | Yes|Length of the entity in UTF-16 code points|



### Type: [MessageEntity](../types/MessageEntity.md)


### Example:

```
$messageEntityPre = ['_' => 'messageEntityPre', 'offset' => int, 'length' => int];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "messageEntityPre", "offset": int, "length": int}
```


Or, if you're into Lua:  


```
messageEntityPre={_='messageEntityPre', offset=int, length=int}

```


