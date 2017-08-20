---
title: inputMediaUploadedThumbDocument
description: inputMediaUploadedThumbDocument attributes, type and example
---
## Constructor: inputMediaUploadedThumbDocument  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|---------------|----------|
|file|[InputFile](../types/InputFile.md) | Yes|
|thumb|[InputFile](../types/InputFile.md) | Yes|
|file\_name|[string](../types/string.md) | Yes|
|mime\_type|[string](../types/string.md) | Yes|



### Type: [InputMedia](../types/InputMedia.md)


### Example:

```
$inputMediaUploadedThumbDocument = ['_' => 'inputMediaUploadedThumbDocument', 'file' => InputFile, 'thumb' => InputFile, 'file_name' => 'string', 'mime_type' => 'string'];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "inputMediaUploadedThumbDocument", "file": InputFile, "thumb": InputFile, "file_name": "string", "mime_type": "string"}
```


Or, if you're into Lua:  


```
inputMediaUploadedThumbDocument={_='inputMediaUploadedThumbDocument', file=InputFile, thumb=InputFile, file_name='string', mime_type='string'}

```


