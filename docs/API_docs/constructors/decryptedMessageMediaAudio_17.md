---
title: decryptedMessageMediaAudio
description: decryptedMessageMediaAudio attributes, type and example
---
## Constructor: decryptedMessageMediaAudio\_17  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|---------------|----------|
|duration|[int](../types/int.md) | Yes|
|mime\_type|[string](../types/string.md) | Yes|
|size|[int](../types/int.md) | Yes|
|key|[bytes](../types/bytes.md) | Yes|
|iv|[bytes](../types/bytes.md) | Yes|



### Type: [DecryptedMessageMedia](../types/DecryptedMessageMedia.md)


### Example:

```
$decryptedMessageMediaAudio_17 = ['_' => 'decryptedMessageMediaAudio', 'duration' => int, 'mime_type' => 'string', 'size' => int, 'key' => 'bytes', 'iv' => 'bytes'];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "decryptedMessageMediaAudio", "duration": int, "mime_type": "string", "size": int, "key": "bytes", "iv": "bytes"}
```


Or, if you're into Lua:  


```
decryptedMessageMediaAudio_17={_='decryptedMessageMediaAudio', duration=int, mime_type='string', size=int, key='bytes', iv='bytes'}

```


