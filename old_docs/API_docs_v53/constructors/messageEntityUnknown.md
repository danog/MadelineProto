---
title: messageEntityUnknown
description: messageEntityUnknown attributes, type and example
---
## Constructor: messageEntityUnknown  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|---------------|----------|
|offset|[int](../types/int.md) | Yes|
|length|[int](../types/int.md) | Yes|



### Type: [MessageEntity](../types/MessageEntity.md)


### Example:

```
$messageEntityUnknown = ['_' => 'messageEntityUnknown', 'offset' => int, 'length' => int];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "messageEntityUnknown", "offset": int, "length": int}
```


Or, if you're into Lua:  


```
messageEntityUnknown={_='messageEntityUnknown', offset=int, length=int}

```


