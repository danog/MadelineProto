---
title: resPQ
description: resPQ attributes, type and example
---
## Constructor: resPQ  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|---------------|----------|
|nonce|[int128](../types/int128.md) | Yes|
|server\_nonce|[int128](../types/int128.md) | Yes|
|pq|[string](../types/string.md) | Yes|
|server\_public\_key\_fingerprints|Array of [long](../types/long.md) | Yes|



### Type: [ResPQ](../types/ResPQ.md)


### Example:

```
$resPQ = ['_' => 'resPQ', 'nonce' => int128, 'server_nonce' => int128, 'pq' => 'string', 'server_public_key_fingerprints' => [long]];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "resPQ", "nonce": int128, "server_nonce": int128, "pq": "string", "server_public_key_fingerprints": [long]}
```


Or, if you're into Lua:  


```
resPQ={_='resPQ', nonce=int128, server_nonce=int128, pq='string', server_public_key_fingerprints={long}}

```


