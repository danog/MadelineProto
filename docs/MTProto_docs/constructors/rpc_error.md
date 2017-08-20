---
title: rpc_error
description: rpc_error attributes, type and example
---
## Constructor: rpc\_error  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|---------------|----------|
|error\_code|[int](../types/int.md) | Yes|
|error\_message|[string](../types/string.md) | Yes|



### Type: [RpcError](../types/RpcError.md)


### Example:

```
$rpc_error = ['_' => 'rpc_error', 'error_code' => int, 'error_message' => 'string'];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "rpc_error", "error_code": int, "error_message": "string"}
```


Or, if you're into Lua:  


```
rpc_error={_='rpc_error', error_code=int, error_message='string'}

```


