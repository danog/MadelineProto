---
title: msgs_state_req
description: msgs_state_req attributes, type and example
---
## Constructor: msgs\_state\_req  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|---------------|----------|
|msg\_ids|Array of [long](../types/long.md) | Yes|



### Type: [MsgsStateReq](../types/MsgsStateReq.md)


### Example:

```
$msgs_state_req = ['_' => 'msgs_state_req', 'msg_ids' => [long]];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "msgs_state_req", "msg_ids": [long]}
```


Or, if you're into Lua:  


```
msgs_state_req={_='msgs_state_req', msg_ids={long}}

```


