---
title: stickerSet
description: stickerSet attributes, type and example
---
## Constructor: stickerSet  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|:-------------:|---------:|
|installed|[Bool](../types/Bool.md) | Optional|
|disabled|[Bool](../types/Bool.md) | Optional|
|official|[Bool](../types/Bool.md) | Optional|
|id|[long](../types/long.md) | Required|
|access\_hash|[long](../types/long.md) | Required|
|title|[string](../types/string.md) | Required|
|short\_name|[string](../types/string.md) | Required|
|count|[int](../types/int.md) | Required|
|hash|[int](../types/int.md) | Required|



### Type: [StickerSet](../types/StickerSet.md)


### Example:

```
$stickerSet = ['_' => 'stickerSet', 'installed' => true, 'disabled' => true, 'official' => true, 'id' => long, 'access_hash' => long, 'title' => string, 'short_name' => string, 'count' => int, 'hash' => int, ];
```  

