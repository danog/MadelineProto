---
title: inputMediaUploadedThumbDocument
description: inputMediaUploadedThumbDocument attributes, type and example
---
## Constructor: inputMediaUploadedThumbDocument  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|:-------------:|---------:|
|file|[InputFile](../types/InputFile.md) | Required|
|thumb|[InputFile](../types/InputFile.md) | Required|
|mime\_type|[string](../types/string.md) | Required|
|attributes|Array of [DocumentAttribute](../types/DocumentAttribute.md) | Required|
|caption|[string](../types/string.md) | Required|



### Type: [InputMedia](../types/InputMedia.md)


### Example:

```
$inputMediaUploadedThumbDocument = ['_' => 'inputMediaUploadedThumbDocument', 'file' => InputFile, 'thumb' => InputFile, 'mime_type' => string, 'attributes' => [Vector t], 'caption' => string, ];
```  

