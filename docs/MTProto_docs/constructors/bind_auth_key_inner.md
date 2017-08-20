---
title: bind_auth_key_inner
description: bind_auth_key_inner attributes, type and example
---
## Constructor: bind\_auth\_key\_inner  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|---------------|----------|
|nonce|[long](../types/long.md) | Yes|
|temp\_auth\_key\_id|[long](../types/long.md) | Yes|
|perm\_auth\_key\_id|[long](../types/long.md) | Yes|
|temp\_session\_id|[long](../types/long.md) | Yes|
|expires\_at|[int](../types/int.md) | Yes|



### Type: [BindAuthKeyInner](../types/BindAuthKeyInner.md)


### Example:

```
$bind_auth_key_inner = ['_' => 'bind_auth_key_inner', 'nonce' => long, 'temp_auth_key_id' => long, 'perm_auth_key_id' => long, 'temp_session_id' => long, 'expires_at' => int];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "bind_auth_key_inner", "nonce": long, "temp_auth_key_id": long, "perm_auth_key_id": long, "temp_session_id": long, "expires_at": int}
```


Or, if you're into Lua:  


```
bind_auth_key_inner={_='bind_auth_key_inner', nonce=long, temp_auth_key_id=long, perm_auth_key_id=long, temp_session_id=long, expires_at=int}

```


