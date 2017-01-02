---
title: rpc_error
description: rpc_error attributes, type and example
---
## Constructor: rpc\_error  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|:-------------:|---------:|
|error\_code|[int](../types/int.md) | Required|
|error\_message|[string](../types/string.md) | Required|



### Type: [RpcError](../types/RpcError.md)


### Example:

```
$rpc_error = ['_' => 'rpc_error', 'error_code' => int, 'error_message' => string, ];
```  

