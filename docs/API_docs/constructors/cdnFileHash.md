---
title: cdnFileHash
description: cdnFileHash attributes, type and example
---
## Constructor: cdnFileHash  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|---------------|----------|
|offset|[int](../types/int.md) | Yes|
|limit|[int](../types/int.md) | Yes|
|hash|[bytes](../types/bytes.md) | Yes|



### Type: [CdnFileHash](../types/CdnFileHash.md)


### Example:

```
$cdnFileHash = ['_' => 'cdnFileHash', 'offset' => int, 'limit' => int, 'hash' => 'bytes'];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "cdnFileHash", "offset": int, "limit": int, "hash": "bytes"}
```


Or, if you're into Lua:  


```
cdnFileHash={_='cdnFileHash', offset=int, limit=int, hash='bytes'}

```


