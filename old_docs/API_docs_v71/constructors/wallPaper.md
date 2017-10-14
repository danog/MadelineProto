---
title: wallPaper
description: wallPaper attributes, type and example
---
## Constructor: wallPaper  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|---------------|----------|
|id|[int](../types/int.md) | Yes|
|title|[string](../types/string.md) | Yes|
|sizes|Array of [PhotoSize](../types/PhotoSize.md) | Yes|
|color|[int](../types/int.md) | Yes|



### Type: [WallPaper](../types/WallPaper.md)


### Example:

```
$wallPaper = ['_' => 'wallPaper', 'id' => int, 'title' => 'string', 'sizes' => [PhotoSize], 'color' => int];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "wallPaper", "id": int, "title": "string", "sizes": [PhotoSize], "color": int}
```


Or, if you're into Lua:  


```
wallPaper={_='wallPaper', id=int, title='string', sizes={PhotoSize}, color=int}

```


