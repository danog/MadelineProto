---
title: decryptedMessageMediaDocument
description: decryptedMessageMediaDocument attributes, type and example
---
## Constructor: decryptedMessageMediaDocument\_45  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|---------------|----------|
|thumb|[bytes](../types/bytes.md) | Yes|
|thumb\_w|[int](../types/int.md) | Yes|
|thumb\_h|[int](../types/int.md) | Yes|
|mime\_type|[string](../types/string.md) | Yes|
|size|[int](../types/int.md) | Yes|
|key|[bytes](../types/bytes.md) | Yes|
|iv|[bytes](../types/bytes.md) | Yes|
|attributes|Array of [DocumentAttribute](../types/DocumentAttribute.md) | Yes|
|caption|[string](../types/string.md) | Yes|



### Type: [DecryptedMessageMedia](../types/DecryptedMessageMedia.md)


### Example:

```
$decryptedMessageMediaDocument_45 = ['_' => 'decryptedMessageMediaDocument', 'thumb' => 'bytes', 'thumb_w' => int, 'thumb_h' => int, 'mime_type' => 'string', 'size' => int, 'key' => 'bytes', 'iv' => 'bytes', 'attributes' => [DocumentAttribute], 'caption' => 'string'];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "decryptedMessageMediaDocument", "thumb": "bytes", "thumb_w": int, "thumb_h": int, "mime_type": "string", "size": int, "key": "bytes", "iv": "bytes", "attributes": [DocumentAttribute], "caption": "string"}
```


Or, if you're into Lua:  


```
decryptedMessageMediaDocument_45={_='decryptedMessageMediaDocument', thumb='bytes', thumb_w=int, thumb_h=int, mime_type='string', size=int, key='bytes', iv='bytes', attributes={DocumentAttribute}, caption='string'}

```


