---
title: server_DH_params_fail
description: server_DH_params_fail attributes, type and example
---
## Constructor: server\_DH\_params\_fail  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|---------------|----------|
|nonce|[int128](../types/int128.md) | Yes|
|server\_nonce|[int128](../types/int128.md) | Yes|
|new\_nonce\_hash|[int128](../types/int128.md) | Yes|



### Type: [Server\_DH\_Params](../types/Server_DH_Params.md)


### Example:

```
$server_DH_params_fail = ['_' => 'server_DH_params_fail', 'nonce' => int128, 'server_nonce' => int128, 'new_nonce_hash' => int128];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "server_DH_params_fail", "nonce": int128, "server_nonce": int128, "new_nonce_hash": int128}
```


Or, if you're into Lua:  


```
server_DH_params_fail={_='server_DH_params_fail', nonce=int128, server_nonce=int128, new_nonce_hash=int128}

```


