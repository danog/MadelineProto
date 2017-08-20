---
title: msgs_state_info
description: msgs_state_info attributes, type and example
---
## Constructor: msgs\_state\_info  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|---------------|----------|
|req\_msg\_id|[long](../types/long.md) | Yes|
|info|[string](../types/string.md) | Yes|



### Type: [MsgsStateInfo](../types/MsgsStateInfo.md)


### Example:

```
$msgs_state_info = ['_' => 'msgs_state_info', 'req_msg_id' => long, 'info' => 'string'];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "msgs_state_info", "req_msg_id": long, "info": "string"}
```


Or, if you're into Lua:  


```
msgs_state_info={_='msgs_state_info', req_msg_id=long, info='string'}

```


