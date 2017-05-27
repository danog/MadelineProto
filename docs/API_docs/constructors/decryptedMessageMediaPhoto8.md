---
title: decryptedMessageMediaPhoto8
description: decryptedMessageMediaPhoto8 attributes, type and example
---
## Constructor: decryptedMessageMediaPhoto8  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|:-------------:|---------:|
|thumb|[bytes](../types/bytes.md) | Yes|
|thumb\_w|[int](../types/int.md) | Yes|
|thumb\_h|[int](../types/int.md) | Yes|
|w|[int](../types/int.md) | Yes|
|h|[int](../types/int.md) | Yes|
|size|[int](../types/int.md) | Yes|
|key|[bytes](../types/bytes.md) | Yes|
|iv|[bytes](../types/bytes.md) | Yes|



### Type: [DecryptedMessageMedia](../types/DecryptedMessageMedia.md)


### Example:

```
$decryptedMessageMediaPhoto8 = ['_' => 'decryptedMessageMediaPhoto8', 'thumb' => bytes, 'thumb_w' => int, 'thumb_h' => int, 'w' => int, 'h' => int, 'size' => int, 'key' => bytes, 'iv' => bytes, ];
```  

Or, if you're into Lua:  


```
decryptedMessageMediaPhoto8={_='decryptedMessageMediaPhoto8', thumb=bytes, thumb_w=int, thumb_h=int, w=int, h=int, size=int, key=bytes, iv=bytes, }

```


