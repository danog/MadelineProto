---
title: foundGif
description: foundGif attributes, type and example
---
## Constructor: foundGif  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|---------------|----------|
|url|[string](../types/string.md) | Yes|
|thumb\_url|[string](../types/string.md) | Yes|
|content\_url|[string](../types/string.md) | Yes|
|content\_type|[string](../types/string.md) | Yes|
|w|[int](../types/int.md) | Yes|
|h|[int](../types/int.md) | Yes|



### Type: [FoundGif](../types/FoundGif.md)


### Example:

```
$foundGif = ['_' => 'foundGif', 'url' => 'string', 'thumb_url' => 'string', 'content_url' => 'string', 'content_type' => 'string', 'w' => int, 'h' => int];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "foundGif", "url": "string", "thumb_url": "string", "content_url": "string", "content_type": "string", "w": int, "h": int}
```


Or, if you're into Lua:  


```
foundGif={_='foundGif', url='string', thumb_url='string', content_url='string', content_type='string', w=int, h=int}

```


