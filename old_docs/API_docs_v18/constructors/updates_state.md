---
title: updates.state
description: updates_state attributes, type and example
---
## Constructor: updates.state  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|---------------|----------|
|pts|[int](../types/int.md) | Yes|
|qts|[int](../types/int.md) | Yes|
|date|[int](../types/int.md) | Yes|
|seq|[int](../types/int.md) | Yes|
|unread\_count|[int](../types/int.md) | Yes|



### Type: [updates\_State](../types/updates_State.md)


### Example:

```
$updates_state = ['_' => 'updates.state', 'pts' => int, 'qts' => int, 'date' => int, 'seq' => int, 'unread_count' => int];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "updates.state", "pts": int, "qts": int, "date": int, "seq": int, "unread_count": int}
```


Or, if you're into Lua:  


```
updates_state={_='updates.state', pts=int, qts=int, date=int, seq=int, unread_count=int}

```


