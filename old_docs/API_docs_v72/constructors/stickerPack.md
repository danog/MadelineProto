---
title: stickerPack
description: stickerPack attributes, type and example
---
## Constructor: stickerPack  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|---------------|----------|
|emoticon|[string](../types/string.md) | Yes|
|documents|Array of [long](../types/long.md) | Yes|



### Type: [StickerPack](../types/StickerPack.md)


### Example:

```
$stickerPack = ['_' => 'stickerPack', 'emoticon' => 'string', 'documents' => [long]];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "stickerPack", "emoticon": "string", "documents": [long]}
```


Or, if you're into Lua:  


```
stickerPack={_='stickerPack', emoticon='string', documents={long}}

```


