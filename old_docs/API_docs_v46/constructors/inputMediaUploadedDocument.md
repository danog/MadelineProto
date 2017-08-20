---
title: inputMediaUploadedDocument
description: inputMediaUploadedDocument attributes, type and example
---
## Constructor: inputMediaUploadedDocument  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|---------------|----------|
|file|[InputFile](../types/InputFile.md) | Yes|
|mime\_type|[string](../types/string.md) | Yes|
|attributes|Array of [DocumentAttribute](../types/DocumentAttribute.md) | Yes|
|caption|[string](../types/string.md) | Yes|



### Type: [InputMedia](../types/InputMedia.md)


### Example:

```
$inputMediaUploadedDocument = ['_' => 'inputMediaUploadedDocument', 'file' => InputFile, 'mime_type' => 'string', 'attributes' => [DocumentAttribute], 'caption' => 'string'];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "inputMediaUploadedDocument", "file": InputFile, "mime_type": "string", "attributes": [DocumentAttribute], "caption": "string"}
```


Or, if you're into Lua:  


```
inputMediaUploadedDocument={_='inputMediaUploadedDocument', file=InputFile, mime_type='string', attributes={DocumentAttribute}, caption='string'}

```


