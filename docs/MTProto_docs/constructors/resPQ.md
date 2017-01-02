---
title: resPQ
description: resPQ attributes, type and example
---
## Constructor: resPQ  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|:-------------:|---------:|
|nonce|[int128](../types/int128.md) | Required|
|server\_nonce|[int128](../types/int128.md) | Required|
|pq|[bytes](../types/bytes.md) | Required|
|server\_public\_key\_fingerprints|Array of [long](../types/long.md) | Required|



### Type: [ResPQ](../types/ResPQ.md)


### Example:

```
$resPQ = ['_' => 'resPQ', 'nonce' => int128, 'server_nonce' => int128, 'pq' => bytes, 'server_public_key_fingerprints' => [Vector t], ];
```  

