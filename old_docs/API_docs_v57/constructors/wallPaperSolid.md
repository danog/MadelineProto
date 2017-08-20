---
title: wallPaperSolid
description: wallPaperSolid attributes, type and example
---
## Constructor: wallPaperSolid  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|---------------|----------|
|id|[int](../types/int.md) | Yes|
|title|[string](../types/string.md) | Yes|
|bg\_color|[int](../types/int.md) | Yes|
|color|[int](../types/int.md) | Yes|



### Type: [WallPaper](../types/WallPaper.md)


### Example:

```
$wallPaperSolid = ['_' => 'wallPaperSolid', 'id' => int, 'title' => 'string', 'bg_color' => int, 'color' => int];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "wallPaperSolid", "id": int, "title": "string", "bg_color": int, "color": int}
```


Or, if you're into Lua:  


```
wallPaperSolid={_='wallPaperSolid', id=int, title='string', bg_color=int, color=int}

```


