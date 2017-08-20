---
title: inputMediaUploadedVideo
description: inputMediaUploadedVideo attributes, type and example
---
## Constructor: inputMediaUploadedVideo  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|---------------|----------|
|file|[InputFile](../types/InputFile.md) | Yes|
|duration|[int](../types/int.md) | Yes|
|w|[int](../types/int.md) | Yes|
|h|[int](../types/int.md) | Yes|
|mime\_type|[string](../types/string.md) | Yes|
|caption|[string](../types/string.md) | Yes|



### Type: [InputMedia](../types/InputMedia.md)


### Example:

```
$inputMediaUploadedVideo = ['_' => 'inputMediaUploadedVideo', 'file' => InputFile, 'duration' => int, 'w' => int, 'h' => int, 'mime_type' => 'string', 'caption' => 'string'];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "inputMediaUploadedVideo", "file": InputFile, "duration": int, "w": int, "h": int, "mime_type": "string", "caption": "string"}
```


Or, if you're into Lua:  


```
inputMediaUploadedVideo={_='inputMediaUploadedVideo', file=InputFile, duration=int, w=int, h=int, mime_type='string', caption='string'}

```


