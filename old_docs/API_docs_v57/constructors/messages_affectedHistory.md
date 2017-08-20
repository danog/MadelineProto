---
title: messages.affectedHistory
description: messages_affectedHistory attributes, type and example
---
## Constructor: messages.affectedHistory  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|---------------|----------|
|pts|[int](../types/int.md) | Yes|
|pts\_count|[int](../types/int.md) | Yes|
|offset|[int](../types/int.md) | Yes|



### Type: [messages\_AffectedHistory](../types/messages_AffectedHistory.md)


### Example:

```
$messages_affectedHistory = ['_' => 'messages.affectedHistory', 'pts' => int, 'pts_count' => int, 'offset' => int];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "messages.affectedHistory", "pts": int, "pts_count": int, "offset": int}
```


Or, if you're into Lua:  


```
messages_affectedHistory={_='messages.affectedHistory', pts=int, pts_count=int, offset=int}

```


