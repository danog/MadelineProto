---
title: p_q_inner_data
description: p_q_inner_data attributes, type and example
---
## Constructor: p\_q\_inner\_data  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|---------------|----------|
|pq|[string](../types/string.md) | Yes|
|p|[string](../types/string.md) | Yes|
|q|[string](../types/string.md) | Yes|
|nonce|[int128](../types/int128.md) | Yes|
|server\_nonce|[int128](../types/int128.md) | Yes|
|new\_nonce|[int256](../types/int256.md) | Yes|



### Type: [P\_Q\_inner\_data](../types/P_Q_inner_data.md)


### Example:

```
$p_q_inner_data = ['_' => 'p_q_inner_data', 'pq' => 'string', 'p' => 'string', 'q' => 'string', 'nonce' => int128, 'server_nonce' => int128, 'new_nonce' => int256];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "p_q_inner_data", "pq": "string", "p": "string", "q": "string", "nonce": int128, "server_nonce": int128, "new_nonce": int256}
```


Or, if you're into Lua:  


```
p_q_inner_data={_='p_q_inner_data', pq='string', p='string', q='string', nonce=int128, server_nonce=int128, new_nonce=int256}

```


