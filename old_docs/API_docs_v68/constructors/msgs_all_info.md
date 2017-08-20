---
title: msgs_all_info
description: msgs_all_info attributes, type and example
---
## Constructor: msgs\_all\_info  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|---------------|----------|
|msg\_ids|Array of [long](../types/long.md) | Yes|
|info|[string](../types/string.md) | Yes|



### Type: [MsgsAllInfo](../types/MsgsAllInfo.md)


### Example:

```
$msgs_all_info = ['_' => 'msgs_all_info', 'msg_ids' => [long], 'info' => 'string'];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "msgs_all_info", "msg_ids": [long], "info": "string"}
```


Or, if you're into Lua:  


```
msgs_all_info={_='msgs_all_info', msg_ids={long}, info='string'}

```


