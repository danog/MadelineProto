---
title: msg_new_detailed_info
description: msg_new_detailed_info attributes, type and example
---
## Constructor: msg\_new\_detailed\_info  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|---------------|----------|
|answer\_msg\_id|[long](../types/long.md) | Yes|
|bytes|[int](../types/int.md) | Yes|
|status|[int](../types/int.md) | Yes|



### Type: [MsgDetailedInfo](../types/MsgDetailedInfo.md)


### Example:

```
$msg_new_detailed_info = ['_' => 'msg_new_detailed_info', 'answer_msg_id' => long, 'bytes' => int, 'status' => int];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "msg_new_detailed_info", "answer_msg_id": long, "bytes": int, "status": int}
```


Or, if you're into Lua:  


```
msg_new_detailed_info={_='msg_new_detailed_info', answer_msg_id=long, bytes=int, status=int}

```


