---
title: decryptedMessageMediaDocument
description: decryptedMessageMediaDocument attributes, type and example
---
## Constructor: decryptedMessageMediaDocument\_8  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|---------------|----------|
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
$decryptedMessageMediaDocument_8 = ['_' => 'decryptedMessageMediaDocument', 'thumb' => 'bytes', 'thumb_w' => int, 'thumb_h' => int, 'file_name' => 'string', 'mime_type' => 'string', 'size' => int, 'key' => 'bytes', 'iv' => 'bytes'];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "decryptedMessageMediaDocument", "thumb": "bytes", "thumb_w": int, "thumb_h": int, "file_name": "string", "mime_type": "string", "size": int, "key": "bytes", "iv": "bytes"}
```


Or, if you're into Lua:  


```
decryptedMessageMediaDocument_8={_='decryptedMessageMediaDocument', thumb='bytes', thumb_w=int, thumb_h=int, file_name='string', mime_type='string', size=int, key='bytes', iv='bytes'}

```


