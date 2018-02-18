---
title: ipPort
description: ipPort attributes, type and example
---
## Constructor: ipPort  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|---------------|----------|
|ipv4|[int](../types/int.md) | Yes|
|port|[int](../types/int.md) | Yes|



### Type: [IpPort](../types/IpPort.md)


### Example:

```
$ipPort = ['_' => 'ipPort', 'ipv4' => int, 'port' => int];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "ipPort", "ipv4": int, "port": int}
```


Or, if you're into Lua:  


```
ipPort={_='ipPort', ipv4=int, port=int}

```


