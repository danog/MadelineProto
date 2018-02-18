---
title: rpc_answer_dropped
description: rpc_answer_dropped attributes, type and example
---
## Constructor: rpc\_answer\_dropped  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|---------------|----------|
|msg\_id|[long](../types/long.md) | Yes|
|seq\_no|[int](../types/int.md) | Yes|
|bytes|[int](../types/int.md) | Yes|



### Type: [RpcDropAnswer](../types/RpcDropAnswer.md)


### Example:

```
$rpc_answer_dropped = ['_' => 'rpc_answer_dropped', 'msg_id' => long, 'seq_no' => int, 'bytes' => int];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "rpc_answer_dropped", "msg_id": long, "seq_no": int, "bytes": int}
```


Or, if you're into Lua:  


```
rpc_answer_dropped={_='rpc_answer_dropped', msg_id=long, seq_no=int, bytes=int}

```


