---
title: resPQ
description: Contains pq to factorize
---
## Constructor: resPQ  
[Back to constructors index](index.md)



Contains pq to factorize

### Attributes:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|nonce|[int128](../types/int128.md) | Yes|Nonce|
|server\_nonce|[int128](../types/int128.md) | Yes|Server nonce|
|pq|[bytes](../types/bytes.md) | Yes||
|server\_public\_key\_fingerprints|Array of [long](../types/long.md) | Yes||



### Type: [ResPQ](../types/ResPQ.md)


### Example:

```
$resPQ = ['_' => 'resPQ', 'nonce' => int128, 'server_nonce' => int128, 'pq' => 'bytes', 'server_public_key_fingerprints' => [long, long]];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "resPQ", "nonce": int128, "server_nonce": int128, "pq": "bytes", "server_public_key_fingerprints": [long]}
```


Or, if you're into Lua:  


```
resPQ={_='resPQ', nonce=int128, server_nonce=int128, pq='bytes', server_public_key_fingerprints={long}}

```


