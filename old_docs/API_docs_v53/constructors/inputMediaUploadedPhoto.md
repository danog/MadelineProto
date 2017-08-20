---
title: inputMediaUploadedPhoto
description: inputMediaUploadedPhoto attributes, type and example
---
## Constructor: inputMediaUploadedPhoto  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|---------------|----------|
|file|[InputFile](../types/InputFile.md) | Yes|
|caption|[string](../types/string.md) | Yes|



### Type: [InputMedia](../types/InputMedia.md)


### Example:

```
$inputMediaUploadedPhoto = ['_' => 'inputMediaUploadedPhoto', 'file' => InputFile, 'caption' => 'string'];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "inputMediaUploadedPhoto", "file": InputFile, "caption": "string"}
```


Or, if you're into Lua:  


```
inputMediaUploadedPhoto={_='inputMediaUploadedPhoto', file=InputFile, caption='string'}

```


