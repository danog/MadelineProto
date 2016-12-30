---
title: p_q_inner_data
description: p_q_inner_data attributes, type and example
---
## Constructor: p\_q\_inner\_data  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|:-------------:|---------:|
|pq|[bytes](../types/bytes.md) | Required|
|p|[bytes](../types/bytes.md) | Required|
|q|[bytes](../types/bytes.md) | Required|
|nonce|[int128](../types/int128.md) | Required|
|server\_nonce|[int128](../types/int128.md) | Required|
|new\_nonce|[int256](../types/int256.md) | Required|



### Type: [P\_Q\_inner\_data](../types/P_Q_inner_data.md)


### Example:

```
$p_q_inner_data = ['_' => p_q_inner_data, 'pq' => bytes, 'p' => bytes, 'q' => bytes, 'nonce' => int128, 'server_nonce' => int128, 'new_nonce' => int256, ];
```