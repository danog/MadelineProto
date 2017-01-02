---
title: inputMediaUploadedAudio
description: inputMediaUploadedAudio attributes, type and example
---
## Constructor: inputMediaUploadedAudio  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|:-------------:|---------:|
|file|[InputFile](../types/InputFile.md) | Required|
|duration|[int](../types/int.md) | Required|
|mime\_type|[string](../types/string.md) | Required|



### Type: [InputMedia](../types/InputMedia.md)


### Example:

```
$inputMediaUploadedAudio = ['_' => 'inputMediaUploadedAudio', 'file' => InputFile, 'duration' => int, 'mime_type' => string, ];
```