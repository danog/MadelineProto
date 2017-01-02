---
title: bind_auth_key_inner
description: bind_auth_key_inner attributes, type and example
---
## Constructor: bind\_auth\_key\_inner  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|:-------------:|---------:|
|nonce|[long](../types/long.md) | Required|
|temp\_auth\_key\_id|[long](../types/long.md) | Required|
|perm\_auth\_key\_id|[long](../types/long.md) | Required|
|temp\_session\_id|[long](../types/long.md) | Required|
|expires\_at|[int](../types/int.md) | Required|



### Type: [BindAuthKeyInner](../types/BindAuthKeyInner.md)


### Example:

```
$bind_auth_key_inner = ['_' => 'bind_auth_key_inner', 'nonce' => long, 'temp_auth_key_id' => long, 'perm_auth_key_id' => long, 'temp_session_id' => long, 'expires_at' => int, ];
```