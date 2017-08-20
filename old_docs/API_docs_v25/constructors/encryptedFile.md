---
title: encryptedFile
description: encryptedFile attributes, type and example
---
## Constructor: encryptedFile  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|---------------|----------|
|id|[long](../types/long.md) | Yes|
|access\_hash|[long](../types/long.md) | Yes|
|size|[int](../types/int.md) | Yes|
|dc\_id|[int](../types/int.md) | Yes|
|key\_fingerprint|[int](../types/int.md) | Yes|



### Type: [EncryptedFile](../types/EncryptedFile.md)


### Example:

```
$encryptedFile = ['_' => 'encryptedFile', 'id' => long, 'access_hash' => long, 'size' => int, 'dc_id' => int, 'key_fingerprint' => int];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "encryptedFile", "id": long, "access_hash": long, "size": int, "dc_id": int, "key_fingerprint": int}
```


Or, if you're into Lua:  


```
encryptedFile={_='encryptedFile', id=long, access_hash=long, size=int, dc_id=int, key_fingerprint=int}

```


