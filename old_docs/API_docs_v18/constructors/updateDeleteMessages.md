---
title: updateDeleteMessages
description: updateDeleteMessages attributes, type and example
---
## Constructor: updateDeleteMessages  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|---------------|----------|
|messages|Array of [int](../types/int.md) | Yes|
|pts|[int](../types/int.md) | Yes|



### Type: [Update](../types/Update.md)


### Example:

```
$updateDeleteMessages = ['_' => 'updateDeleteMessages', 'messages' => [int], 'pts' => int];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "updateDeleteMessages", "messages": [int], "pts": int}
```


Or, if you're into Lua:  


```
updateDeleteMessages={_='updateDeleteMessages', messages={int}, pts=int}

```


