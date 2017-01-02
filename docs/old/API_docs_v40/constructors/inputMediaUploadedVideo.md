---
title: inputMediaUploadedVideo
description: inputMediaUploadedVideo attributes, type and example
---
## Constructor: inputMediaUploadedVideo  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|:-------------:|---------:|
|file|[InputFile](../types/InputFile.md) | Required|
|duration|[int](../types/int.md) | Required|
|w|[int](../types/int.md) | Required|
|h|[int](../types/int.md) | Required|
|mime\_type|[string](../types/string.md) | Required|
|caption|[string](../types/string.md) | Required|



### Type: [InputMedia](../types/InputMedia.md)


### Example:

```
$inputMediaUploadedVideo = ['_' => 'inputMediaUploadedVideo', 'file' => InputFile, 'duration' => int, 'w' => int, 'h' => int, 'mime_type' => string, 'caption' => string, ];
```  

