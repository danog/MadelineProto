---
title: decryptedMessageMediaAudio
description: decryptedMessageMediaAudio attributes, type and example
---
## Constructor: decryptedMessageMediaAudio\_8  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|---------------|----------|
|duration|[int](../types/int.md) | Yes|
|size|[int](../types/int.md) | Yes|
|key|[bytes](../types/bytes.md) | Yes|
|iv|[bytes](../types/bytes.md) | Yes|



### Type: [DecryptedMessageMedia](../types/DecryptedMessageMedia.md)


### Example:

```
$decryptedMessageMediaAudio_8 = ['_' => 'decryptedMessageMediaAudio', 'duration' => int, 'size' => int, 'key' => 'bytes', 'iv' => 'bytes'];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "decryptedMessageMediaAudio", "duration": int, "size": int, "key": "bytes", "iv": "bytes"}
```


Or, if you're into Lua:  


```
decryptedMessageMediaAudio_8={_='decryptedMessageMediaAudio', duration=int, size=int, key='bytes', iv='bytes'}

```


