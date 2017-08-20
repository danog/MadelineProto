---
title: messageEntityCode
description: Text needs to be formatted as inside of code HTML tag
---
## Constructor: messageEntityCode  
[Back to constructors index](index.md)



Text needs to be formatted as inside of code HTML tag

### Attributes:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|offset|[int](../types/int.md) | Yes|Offset of the entity in UTF-16 code points|
|length|[int](../types/int.md) | Yes|Length of the entity in UTF-16 code points|



### Type: [MessageEntity](../types/MessageEntity.md)


### Example:

```
$messageEntityCode = ['_' => 'messageEntityCode', 'offset' => int, 'length' => int];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "messageEntityCode", "offset": int, "length": int}
```


Or, if you're into Lua:  


```
messageEntityCode={_='messageEntityCode', offset=int, length=int}

```


