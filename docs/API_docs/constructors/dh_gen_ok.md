---
title: dh_gen_ok
description: dh_gen_ok attributes, type and example
---
## Constructor: dh\_gen\_ok  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|:-------------:|---------:|
|nonce|[int128](../types/int128.md) | Required|
|server\_nonce|[int128](../types/int128.md) | Required|
|new\_nonce\_hash1|[int128](../types/int128.md) | Required|



### Type: [Set\_client\_DH\_params\_answer](../types/Set_client_DH_params_answer.md)


### Example:

```
$dh_gen_ok = ['_' => dh_gen_ok, 'nonce' => int128, 'server_nonce' => int128, 'new_nonce_hash1' => int128, ];
```