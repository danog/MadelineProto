---
title: messageEntityBold
description: messageEntityBold attributes, type and example
---
## Constructor: messageEntityBold  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|---------------|----------|
|offset|[int](../types/int.md) | Yes|
|length|[int](../types/int.md) | Yes|



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


