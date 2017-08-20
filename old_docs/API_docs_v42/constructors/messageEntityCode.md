---
title: messageEntityCode
description: messageEntityCode attributes, type and example
---
## Constructor: messageEntityCode  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|---------------|----------|
|offset|[int](../types/int.md) | Yes|
|length|[int](../types/int.md) | Yes|



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


