---
title: updateReadMessages
description: updateReadMessages attributes, type and example
---
## Constructor: updateReadMessages  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|---------------|----------|
|messages|Array of [int](../types/int.md) | Yes|
|pts|[int](../types/int.md) | Yes|
|pts\_count|[int](../types/int.md) | Yes|



### Type: [Update](../types/Update.md)


### Example:

```
$updateReadMessages = ['_' => 'updateReadMessages', 'messages' => [int], 'pts' => int, 'pts_count' => int];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "updateReadMessages", "messages": [int], "pts": int, "pts_count": int}
```


Or, if you're into Lua:  


```
updateReadMessages={_='updateReadMessages', messages={int}, pts=int, pts_count=int}

```


