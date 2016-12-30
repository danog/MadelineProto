---
title: client_DH_inner_data
description: client_DH_inner_data attributes, type and example
---
## Constructor: client\_DH\_inner\_data  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|:-------------:|---------:|
|nonce|[int128](../types/int128.md) | Required|
|server\_nonce|[int128](../types/int128.md) | Required|
|retry\_id|[long](../types/long.md) | Required|
|g\_b|[bytes](../types/bytes.md) | Required|



### Type: [Client\_DH\_Inner\_Data](../types/Client_DH_Inner_Data.md)


### Example:

```
$client_DH_inner_data = ['_' => 'client_DH_inner_data', 'nonce' => int128, 'server_nonce' => int128, 'retry_id' => long, 'g_b' => bytes, ];
```