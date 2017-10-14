---
title: msg_resend_req
description: msg_resend_req attributes, type and example
---
## Constructor: msg\_resend\_req  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|---------------|----------|
|msg\_ids|Array of [long](../types/long.md) | Yes|



### Type: [MsgResendReq](../types/MsgResendReq.md)


### Example:

```
$msg_resend_req = ['_' => 'msg_resend_req', 'msg_ids' => [long]];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "msg_resend_req", "msg_ids": [long]}
```


Or, if you're into Lua:  


```
msg_resend_req={_='msg_resend_req', msg_ids={long}}

```


