---
title: photoSize
description: Photo description
---
## Constructor: photoSize  
[Back to constructors index](index.md)



Photo description

### Attributes:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|type|[string](../types/string.md) | Yes|Thumbnail type (see https: core.telegram.org/constructor/photoSize)|
|photo|[file](../types/file.md) | Yes|Information about photo file|
|width|[int](../types/int.md) | Yes|Photo width|
|height|[int](../types/int.md) | Yes|Photo height|



### Type: [PhotoSize](../types/PhotoSize.md)


### Example:

```
$photoSize = ['_' => 'photoSize', 'type' => 'string', 'photo' => file, 'width' => int, 'height' => int];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "photoSize", "type": "string", "photo": file, "width": int, "height": int}
```


Or, if you're into Lua:  


```
photoSize={_='photoSize', type='string', photo=file, width=int, height=int}

```


