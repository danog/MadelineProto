---
title: updates.differenceEmpty
description: updates_differenceEmpty attributes, type and example
---
## Constructor: updates.differenceEmpty  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|---------------|----------|
|date|[int](../types/int.md) | Yes|
|seq|[int](../types/int.md) | Yes|



### Type: [updates\_Difference](../types/updates_Difference.md)


### Example:

```
$updates_differenceEmpty = ['_' => 'updates.differenceEmpty', 'date' => int, 'seq' => int];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "updates.differenceEmpty", "date": int, "seq": int}
```


Or, if you're into Lua:  


```
updates_differenceEmpty={_='updates.differenceEmpty', date=int, seq=int}

```


