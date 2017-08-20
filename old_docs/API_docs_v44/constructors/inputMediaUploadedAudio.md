---
title: inputMediaUploadedAudio
description: inputMediaUploadedAudio attributes, type and example
---
## Constructor: inputMediaUploadedAudio  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|---------------|----------|
|file|[InputFile](../types/InputFile.md) | Yes|
|duration|[int](../types/int.md) | Yes|
|mime\_type|[string](../types/string.md) | Yes|



### Type: [InputMedia](../types/InputMedia.md)


### Example:

```
$inputMediaUploadedAudio = ['_' => 'inputMediaUploadedAudio', 'file' => InputFile, 'duration' => int, 'mime_type' => 'string'];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "inputMediaUploadedAudio", "file": InputFile, "duration": int, "mime_type": "string"}
```


Or, if you're into Lua:  


```
inputMediaUploadedAudio={_='inputMediaUploadedAudio', file=InputFile, duration=int, mime_type='string'}

```


