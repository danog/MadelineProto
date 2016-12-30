---
title: server_DH_params_ok
description: server_DH_params_ok attributes, type and example
---
## Constructor: server\_DH\_params\_ok  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|:-------------:|---------:|
|nonce|[int128](../types/int128.md) | Required|
|server\_nonce|[int128](../types/int128.md) | Required|
|encrypted\_answer|[bytes](../types/bytes.md) | Required|



### Type: [Server\_DH\_Params](../types/Server_DH_Params.md)


### Example:

```
$server_DH_params_ok = ['_' => server_DH_params_ok, 'nonce' => int128, 'server_nonce' => int128, 'encrypted_answer' => bytes, ];
```