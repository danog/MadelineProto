---
title: messageEntityPre
description: messageEntityPre attributes, type and example
---
## Constructor: messageEntityPre  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|---------------|----------|
|offset|[int](../types/int.md) | Yes|
|length|[int](../types/int.md) | Yes|
|language|[string](../types/string.md) | Yes|



### Type: [MessageEntity](../types/MessageEntity.md)


### Example:

```
$messageEntityPre = ['_' => 'messageEntityPre', 'offset' => int, 'length' => int, 'language' => 'string'];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "messageEntityPre", "offset": int, "length": int, "language": "string"}
```


Or, if you're into Lua:  


```
messageEntityPre={_='messageEntityPre', offset=int, length=int, language='string'}

```


