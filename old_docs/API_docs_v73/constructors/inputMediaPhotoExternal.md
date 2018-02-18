---
title: inputMediaPhotoExternal
description: inputMediaPhotoExternal attributes, type and example
---
## Constructor: inputMediaPhotoExternal  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|---------------|----------|
|url|[string](../types/string.md) | Yes|
|caption|[string](../types/string.md) | Yes|
|ttl\_seconds|[int](../types/int.md) | Optional|



### Type: [InputMedia](../types/InputMedia.md)


### Example:

```
$inputMediaPhotoExternal = ['_' => 'inputMediaPhotoExternal', 'url' => 'string', 'caption' => 'string', 'ttl_seconds' => int];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "inputMediaPhotoExternal", "url": "string", "caption": "string", "ttl_seconds": int}
```


Or, if you're into Lua:  


```
inputMediaPhotoExternal={_='inputMediaPhotoExternal', url='string', caption='string', ttl_seconds=int}

```


