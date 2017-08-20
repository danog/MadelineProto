---
title: messageEntityHashtag
description: messageEntityHashtag attributes, type and example
---
## Constructor: messageEntityHashtag  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|---------------|----------|
|offset|[int](../types/int.md) | Yes|
|length|[int](../types/int.md) | Yes|



### Type: [MessageEntity](../types/MessageEntity.md)


### Example:

```
$messageEntityHashtag = ['_' => 'messageEntityHashtag', 'offset' => int, 'length' => int];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "messageEntityHashtag", "offset": int, "length": int}
```


Or, if you're into Lua:  


```
messageEntityHashtag={_='messageEntityHashtag', offset=int, length=int}

```


