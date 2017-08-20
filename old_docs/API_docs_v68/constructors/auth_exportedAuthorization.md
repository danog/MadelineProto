---
title: auth.exportedAuthorization
description: auth_exportedAuthorization attributes, type and example
---
## Constructor: auth.exportedAuthorization  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|---------------|----------|
|id|[int](../types/int.md) | Yes|
|bytes|[bytes](../types/bytes.md) | Yes|



### Type: [auth\_ExportedAuthorization](../types/auth_ExportedAuthorization.md)


### Example:

```
$auth_exportedAuthorization = ['_' => 'auth.exportedAuthorization', 'id' => int, 'bytes' => 'bytes'];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "auth.exportedAuthorization", "id": int, "bytes": "bytes"}
```


Or, if you're into Lua:  


```
auth_exportedAuthorization={_='auth.exportedAuthorization', id=int, bytes='bytes'}

```


