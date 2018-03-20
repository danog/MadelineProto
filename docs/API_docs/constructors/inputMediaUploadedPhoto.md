---
title: inputMediaUploadedPhoto
description: inputMediaUploadedPhoto attributes, type and example
---
## Constructor: inputMediaUploadedPhoto  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|---------------|----------|
|file|[File path or InputFile](../types/InputFile.md) | Yes|
|stickers|Array of [MessageMedia, Message, Update or InputDocument](../types/InputDocument.md) | Optional|
|ttl\_seconds|[int](../types/int.md) | Optional|



### Type: [InputMedia](../types/InputMedia.md)


### Example:

```
$inputMediaUploadedPhoto = ['_' => 'inputMediaUploadedPhoto', 'file' => InputFile, 'stickers' => [InputDocument, InputDocument], 'ttl_seconds' => int];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "inputMediaUploadedPhoto", "file": InputFile, "stickers": [InputDocument], "ttl_seconds": int}
```


Or, if you're into Lua:  


```
inputMediaUploadedPhoto={_='inputMediaUploadedPhoto', file=InputFile, stickers={InputDocument}, ttl_seconds=int}

```


