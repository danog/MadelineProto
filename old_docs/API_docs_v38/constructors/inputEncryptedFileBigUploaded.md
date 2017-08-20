---
title: inputEncryptedFileBigUploaded
description: inputEncryptedFileBigUploaded attributes, type and example
---
## Constructor: inputEncryptedFileBigUploaded  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|---------------|----------|
|id|[long](../types/long.md) | Yes|
|parts|[int](../types/int.md) | Yes|
|key\_fingerprint|[int](../types/int.md) | Yes|



### Type: [InputEncryptedFile](../types/InputEncryptedFile.md)


### Example:

```
$inputEncryptedFileBigUploaded = ['_' => 'inputEncryptedFileBigUploaded', 'id' => long, 'parts' => int, 'key_fingerprint' => int];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "inputEncryptedFileBigUploaded", "id": long, "parts": int, "key_fingerprint": int}
```


Or, if you're into Lua:  


```
inputEncryptedFileBigUploaded={_='inputEncryptedFileBigUploaded', id=long, parts=int, key_fingerprint=int}

```


