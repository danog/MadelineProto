---
title: help.configSimple
description: help_configSimple attributes, type and example
---
## Constructor: help.configSimple  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|---------------|----------|
|date|[int](../types/int.md) | Yes|
|expires|[int](../types/int.md) | Yes|
|dc\_id|[int](../types/int.md) | Yes|
|ip\_port\_list|Array of [ipPort](../types/ipPort.md) | Yes|



### Type: [help\_ConfigSimple](../types/help_ConfigSimple.md)


### Example:

```
$help_configSimple = ['_' => 'help.configSimple', 'date' => int, 'expires' => int, 'dc_id' => int, 'ip_port_list' => [ipPort]];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "help.configSimple", "date": int, "expires": int, "dc_id": int, "ip_port_list": [ipPort]}
```


Or, if you're into Lua:  


```
help_configSimple={_='help.configSimple', date=int, expires=int, dc_id=int, ip_port_list={ipPort}}

```


