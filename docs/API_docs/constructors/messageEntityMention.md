---
title: messageEntityMention
description: messageEntityMention attributes, type and example
---
## Constructor: messageEntityMention  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|---------------|----------|
|offset|[int](../types/int.md) | Yes|
|length|[int](../types/int.md) | Yes|



### Type: [MessageEntity](../types/MessageEntity.md)


### Example:

```
$messageEntityMention = ['_' => 'messageEntityMention', 'offset' => int, 'length' => int];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "messageEntityMention", "offset": int, "length": int}
```


Or, if you're into Lua:  


```
messageEntityMention={_='messageEntityMention', offset=int, length=int}

```


