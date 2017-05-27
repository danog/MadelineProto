---
title: decryptedMessageMediaAudio17
description: decryptedMessageMediaAudio17 attributes, type and example
---
## Constructor: decryptedMessageMediaAudio17  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|:-------------:|---------:|
|duration|[int](../types/int.md) | Yes|
|mime\_type|[string](../types/string.md) | Yes|
|size|[int](../types/int.md) | Yes|
|key|[bytes](../types/bytes.md) | Yes|
|iv|[bytes](../types/bytes.md) | Yes|



### Type: [DecryptedMessageMedia](../types/DecryptedMessageMedia.md)


### Example:

```
$decryptedMessageMediaAudio17 = ['_' => 'decryptedMessageMediaAudio17', 'duration' => int, 'mime_type' => string, 'size' => int, 'key' => bytes, 'iv' => bytes, ];
```  

Or, if you're into Lua:  


```
decryptedMessageMediaAudio17={_='decryptedMessageMediaAudio17', duration=int, mime_type=string, size=int, key=bytes, iv=bytes, }

```


