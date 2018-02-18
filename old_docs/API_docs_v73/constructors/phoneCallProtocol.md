---
title: phoneCallProtocol
description: phoneCallProtocol attributes, type and example
---
## Constructor: phoneCallProtocol  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|---------------|----------|
|udp\_p2p|[Bool](../types/Bool.md) | Optional|
|udp\_reflector|[Bool](../types/Bool.md) | Optional|
|min\_layer|[int](../types/int.md) | Yes|
|max\_layer|[int](../types/int.md) | Yes|



### Type: [PhoneCallProtocol](../types/PhoneCallProtocol.md)


### Example:

```
$phoneCallProtocol = ['_' => 'phoneCallProtocol', 'udp_p2p' => Bool, 'udp_reflector' => Bool, 'min_layer' => int, 'max_layer' => int];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "phoneCallProtocol", "udp_p2p": Bool, "udp_reflector": Bool, "min_layer": int, "max_layer": int}
```


Or, if you're into Lua:  


```
phoneCallProtocol={_='phoneCallProtocol', udp_p2p=Bool, udp_reflector=Bool, min_layer=int, max_layer=int}

```


