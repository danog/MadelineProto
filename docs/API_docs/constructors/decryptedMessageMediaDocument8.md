---
title: decryptedMessageMediaDocument8
description: decryptedMessageMediaDocument8 attributes, type and example
---
## Constructor: decryptedMessageMediaDocument8  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|:-------------:|---------:|
|thumb|[bytes](../types/bytes.md) | Yes|
|thumb\_w|[int](../types/int.md) | Yes|
|thumb\_h|[int](../types/int.md) | Yes|
|file\_name|[string](../types/string.md) | Yes|
|mime\_type|[string](../types/string.md) | Yes|
|size|[int](../types/int.md) | Yes|
|key|[bytes](../types/bytes.md) | Yes|
|iv|[bytes](../types/bytes.md) | Yes|



### Type: [DecryptedMessageMedia](../types/DecryptedMessageMedia.md)


### Example:

```
$decryptedMessageMediaDocument8 = ['_' => 'decryptedMessageMediaDocument8', 'thumb' => bytes, 'thumb_w' => int, 'thumb_h' => int, 'file_name' => string, 'mime_type' => string, 'size' => int, 'key' => bytes, 'iv' => bytes, ];
```  

Or, if you're into Lua:  


```
decryptedMessageMediaDocument8={_='decryptedMessageMediaDocument8', thumb=bytes, thumb_w=int, thumb_h=int, file_name=string, mime_type=string, size=int, key=bytes, iv=bytes, }

```


