---
title: decryptedMessageMediaVideo8
description: decryptedMessageMediaVideo8 attributes, type and example
---
## Constructor: decryptedMessageMediaVideo8  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|:-------------:|---------:|
|thumb|[bytes](../types/bytes.md) | Yes|
|thumb\_w|[int](../types/int.md) | Yes|
|thumb\_h|[int](../types/int.md) | Yes|
|duration|[int](../types/int.md) | Yes|
|w|[int](../types/int.md) | Yes|
|h|[int](../types/int.md) | Yes|
|size|[int](../types/int.md) | Yes|
|key|[bytes](../types/bytes.md) | Yes|
|iv|[bytes](../types/bytes.md) | Yes|



### Type: [DecryptedMessageMedia](../types/DecryptedMessageMedia.md)


### Example:

```
$decryptedMessageMediaVideo8 = ['_' => 'decryptedMessageMediaVideo8', 'thumb' => bytes, 'thumb_w' => int, 'thumb_h' => int, 'duration' => int, 'w' => int, 'h' => int, 'size' => int, 'key' => bytes, 'iv' => bytes, ];
```  

Or, if you're into Lua:  


```
decryptedMessageMediaVideo8={_='decryptedMessageMediaVideo8', thumb=bytes, thumb_w=int, thumb_h=int, duration=int, w=int, h=int, size=int, key=bytes, iv=bytes, }

```


