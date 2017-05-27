---
title: decryptedMessageMediaAudio8
description: decryptedMessageMediaAudio8 attributes, type and example
---
## Constructor: decryptedMessageMediaAudio8  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|:-------------:|---------:|
|duration|[int](../types/int.md) | Yes|
|size|[int](../types/int.md) | Yes|
|key|[bytes](../types/bytes.md) | Yes|
|iv|[bytes](../types/bytes.md) | Yes|



### Type: [DecryptedMessageMedia](../types/DecryptedMessageMedia.md)


### Example:

```
$decryptedMessageMediaAudio8 = ['_' => 'decryptedMessageMediaAudio8', 'duration' => int, 'size' => int, 'key' => bytes, 'iv' => bytes, ];
```  

Or, if you're into Lua:  


```
decryptedMessageMediaAudio8={_='decryptedMessageMediaAudio8', duration=int, size=int, key=bytes, iv=bytes, }

```


