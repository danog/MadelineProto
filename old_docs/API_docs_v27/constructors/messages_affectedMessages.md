---
title: messages.affectedMessages
description: messages_affectedMessages attributes, type and example
---
## Constructor: messages.affectedMessages  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|---------------|----------|
|pts|[int](../types/int.md) | Yes|
|pts\_count|[int](../types/int.md) | Yes|



### Type: [messages\_AffectedMessages](../types/messages_AffectedMessages.md)


### Example:

```
$messages_affectedMessages = ['_' => 'messages.affectedMessages', 'pts' => int, 'pts_count' => int];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "messages.affectedMessages", "pts": int, "pts_count": int}
```


Or, if you're into Lua:  


```
messages_affectedMessages={_='messages.affectedMessages', pts=int, pts_count=int}

```


