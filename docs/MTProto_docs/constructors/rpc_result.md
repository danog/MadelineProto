---
title: rpc_result
description: rpc_result attributes, type and example
---
## Constructor: rpc\_result  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|---------------|----------|
|req\_msg\_id|[long](../types/long.md) | Yes|
|result|[Object](../types/Object.md) | Yes|



### Type: [RpcResult](../types/RpcResult.md)


### Example:

```
$rpc_result = ['_' => 'rpc_result', 'req_msg_id' => long, 'result' => Object];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "rpc_result", "req_msg_id": long, "result": Object}
```


Or, if you're into Lua:  


```
rpc_result={_='rpc_result', req_msg_id=long, result=Object}

```


