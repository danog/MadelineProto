---
title: foundGifCached
description: foundGifCached attributes, type and example
---
## Constructor: foundGifCached  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|---------------|----------|
|url|[string](../types/string.md) | Yes|
|photo|[Photo](../types/Photo.md) | Yes|
|document|[Document](../types/Document.md) | Yes|



### Type: [FoundGif](../types/FoundGif.md)


### Example:

```
$foundGifCached = ['_' => 'foundGifCached', 'url' => 'string', 'photo' => Photo, 'document' => Document];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "foundGifCached", "url": "string", "photo": Photo, "document": Document}
```


Or, if you're into Lua:  


```
foundGifCached={_='foundGifCached', url='string', photo=Photo, document=Document}

```


