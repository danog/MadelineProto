---
title: decryptedMessageMediaPhoto
description: decryptedMessageMediaPhoto attributes, type and example
---
## Constructor: decryptedMessageMediaPhoto\_45  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|---------------|----------|
|thumb|[bytes](../types/bytes.md) | Yes|
|thumb\_w|[int](../types/int.md) | Yes|
|thumb\_h|[int](../types/int.md) | Yes|
|w|[int](../types/int.md) | Yes|
|h|[int](../types/int.md) | Yes|
|size|[int](../types/int.md) | Yes|
|key|[bytes](../types/bytes.md) | Yes|
|iv|[bytes](../types/bytes.md) | Yes|
|caption|[string](../types/string.md) | Yes|



### Type: [DecryptedMessageMedia](../types/DecryptedMessageMedia.md)


### Example:

```
$decryptedMessageMediaPhoto_45 = ['_' => 'decryptedMessageMediaPhoto', 'thumb' => 'bytes', 'thumb_w' => int, 'thumb_h' => int, 'w' => int, 'h' => int, 'size' => int, 'key' => 'bytes', 'iv' => 'bytes', 'caption' => 'string'];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "decryptedMessageMediaPhoto", "thumb": "bytes", "thumb_w": int, "thumb_h": int, "w": int, "h": int, "size": int, "key": "bytes", "iv": "bytes", "caption": "string"}
```


Or, if you're into Lua:  


```
decryptedMessageMediaPhoto_45={_='decryptedMessageMediaPhoto', thumb='bytes', thumb_w=int, thumb_h=int, w=int, h=int, size=int, key='bytes', iv='bytes', caption='string'}

```


