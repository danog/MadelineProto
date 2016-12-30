---
title: server_DH_inner_data
description: server_DH_inner_data attributes, type and example
---
## Constructor: server\_DH\_inner\_data  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|:-------------:|---------:|
|nonce|[int128](../types/int128.md) | Required|
|server\_nonce|[int128](../types/int128.md) | Required|
|g|[int](../types/int.md) | Required|
|dh\_prime|[bytes](../types/bytes.md) | Required|
|g\_a|[bytes](../types/bytes.md) | Required|
|server\_time|[int](../types/int.md) | Required|



### Type: [Server\_DH\_inner\_data](../types/Server_DH_inner_data.md)


### Example:

```
$server_DH_inner_data = ['_' => server_DH_inner_data, 'nonce' => int128, 'server_nonce' => int128, 'g' => int, 'dh_prime' => bytes, 'g_a' => bytes, 'server_time' => int, ];
```