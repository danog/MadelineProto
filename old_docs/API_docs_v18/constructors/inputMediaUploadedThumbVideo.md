---
title: inputMediaUploadedThumbVideo
description: inputMediaUploadedThumbVideo attributes, type and example
---
## Constructor: inputMediaUploadedThumbVideo  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|---------------|----------|
|file|[File path or InputFile](../types/InputFile.md) | Yes|
|thumb|[File path or InputFile](../types/InputFile.md) | Yes|
|duration|[int](../types/int.md) | Yes|
|w|[int](../types/int.md) | Yes|
|h|[int](../types/int.md) | Yes|
|mime\_type|[string](../types/string.md) | Optional|



### Type: [InputMedia](../types/InputMedia.md)


### Example:

```
$inputMediaUploadedThumbVideo = ['_' => 'inputMediaUploadedThumbVideo', 'file' => InputFile, 'thumb' => InputFile, 'duration' => int, 'w' => int, 'h' => int, 'mime_type' => 'string'];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "inputMediaUploadedThumbVideo", "file": InputFile, "thumb": InputFile, "duration": int, "w": int, "h": int, "mime_type": "string"}
```


Or, if you're into Lua:  


```
inputMediaUploadedThumbVideo={_='inputMediaUploadedThumbVideo', file=InputFile, thumb=InputFile, duration=int, w=int, h=int, mime_type='string'}

```


