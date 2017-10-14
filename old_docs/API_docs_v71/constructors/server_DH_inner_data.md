---
title: server_DH_inner_data
description: server_DH_inner_data attributes, type and example
---
## Constructor: server\_DH\_inner\_data  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|---------------|----------|
|nonce|[int128](../types/int128.md) | Yes|
|server\_nonce|[int128](../types/int128.md) | Yes|
|g|[int](../types/int.md) | Yes|
|dh\_prime|[string](../types/string.md) | Yes|
|g\_a|[string](../types/string.md) | Yes|
|server\_time|[int](../types/int.md) | Yes|



### Type: [Server\_DH\_inner\_data](../types/Server_DH_inner_data.md)


### Example:

```
$server_DH_inner_data = ['_' => 'server_DH_inner_data', 'nonce' => int128, 'server_nonce' => int128, 'g' => int, 'dh_prime' => 'string', 'g_a' => 'string', 'server_time' => int];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "server_DH_inner_data", "nonce": int128, "server_nonce": int128, "g": int, "dh_prime": "string", "g_a": "string", "server_time": int}
```


Or, if you're into Lua:  


```
server_DH_inner_data={_='server_DH_inner_data', nonce=int128, server_nonce=int128, g=int, dh_prime='string', g_a='string', server_time=int}

```


