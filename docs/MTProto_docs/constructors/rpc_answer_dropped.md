---
title: rpc_answer_dropped
description: rpc_answer_dropped attributes, type and example
---
## Constructor: rpc\_answer\_dropped  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|:-------------:|---------:|
|msg\_id|[long](../types/long.md) | Required|
|seq\_no|[int](../types/int.md) | Required|
|bytes|[int](../types/int.md) | Required|



### Type: [RpcDropAnswer](../types/RpcDropAnswer.md)


### Example:

```
$rpc_answer_dropped = ['_' => 'rpc_answer_dropped', 'msg_id' => long, 'seq_no' => int, 'bytes' => int, ];
```  

