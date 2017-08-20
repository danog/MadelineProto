---
title: inputEncryptedFileUploaded
description: inputEncryptedFileUploaded attributes, type and example
---
## Constructor: inputEncryptedFileUploaded  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|---------------|----------|
|id|[long](../types/long.md) | Yes|
|parts|[int](../types/int.md) | Yes|
|md5\_checksum|[string](../types/string.md) | Yes|
|key\_fingerprint|[int](../types/int.md) | Yes|



### Type: [InputEncryptedFile](../types/InputEncryptedFile.md)


### Example:

```
$inputEncryptedFileUploaded = ['_' => 'inputEncryptedFileUploaded', 'id' => long, 'parts' => int, 'md5_checksum' => 'string', 'key_fingerprint' => int];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "inputEncryptedFileUploaded", "id": long, "parts": int, "md5_checksum": "string", "key_fingerprint": int}
```


Or, if you're into Lua:  


```
inputEncryptedFileUploaded={_='inputEncryptedFileUploaded', id=long, parts=int, md5_checksum='string', key_fingerprint=int}

```


