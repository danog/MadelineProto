---
title: messageEntityBold
description: Bold text
---
## Constructor: messageEntityBold  
[Back to constructors index](index.md)



Bold text

### Attributes:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|offset|[int](../types/int.md) | Yes|Offset of the entity in UTF-16 code points|
|length|[int](../types/int.md) | Yes|Length of the entity in UTF-16 code points|



### Type: [MessageEntity](../types/MessageEntity.md)


### Example:

```
$messageEntityBold = ['_' => 'messageEntityBold', 'offset' => int, 'length' => int];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "messageEntityBold", "offset": int, "length": int}
```


Or, if you're into Lua:  


```
messageEntityBold={_='messageEntityBold', offset=int, length=int}

```


