---
title: inputMediaUploadedPhoto
description: inputMediaUploadedPhoto attributes, type and example
---
## Constructor: inputMediaUploadedPhoto  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|:-------------:|---------:|
|file|[InputFile](../types/InputFile.md) | Required|
|caption|[string](../types/string.md) | Required|
|stickers|Array of [InputDocument](../types/InputDocument.md) | Optional|



### Type: [InputMedia](../types/InputMedia.md)


### Example:

```
$inputMediaUploadedPhoto = ['_' => 'inputMediaUploadedPhoto', 'file' => InputFile, 'caption' => string, 'stickers' => [Vector t], ];
```