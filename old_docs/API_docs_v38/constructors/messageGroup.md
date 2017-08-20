---
title: messageGroup
description: messageGroup attributes, type and example
---
## Constructor: messageGroup  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|---------------|----------|
|min\_id|[int](../types/int.md) | Yes|
|max\_id|[int](../types/int.md) | Yes|
|count|[int](../types/int.md) | Yes|
|date|[int](../types/int.md) | Yes|



### Type: [MessageGroup](../types/MessageGroup.md)


### Example:

```
$messageGroup = ['_' => 'messageGroup', 'min_id' => int, 'max_id' => int, 'count' => int, 'date' => int];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "messageGroup", "min_id": int, "max_id": int, "count": int, "date": int}
```


Or, if you're into Lua:  


```
messageGroup={_='messageGroup', min_id=int, max_id=int, count=int, date=int}

```


