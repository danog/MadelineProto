---
title: messageEntityUrl
description: messageEntityUrl attributes, type and example
---
## Constructor: messageEntityUrl  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|---------------|----------|
|offset|[int](../types/int.md) | Yes|
|length|[int](../types/int.md) | Yes|



### Type: [MessageEntity](../types/MessageEntity.md)


### Example:

```
$messageEntityUrl = ['_' => 'messageEntityUrl', 'offset' => int, 'length' => int];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "messageEntityUrl", "offset": int, "length": int}
```


Or, if you're into Lua:  


```
messageEntityUrl={_='messageEntityUrl', offset=int, length=int}

```


