---
title: dcOption
description: dcOption attributes, type and example
---
## Constructor: dcOption  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|---------------|----------|
|id|[int](../types/int.md) | Yes|
|ip\_address|[string](../types/string.md) | Yes|
|port|[int](../types/int.md) | Yes|



### Type: [DcOption](../types/DcOption.md)


### Example:

```
$dcOption = ['_' => 'dcOption', 'id' => int, 'ip_address' => 'string', 'port' => int];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "dcOption", "id": int, "ip_address": "string", "port": int}
```


Or, if you're into Lua:  


```
dcOption={_='dcOption', id=int, ip_address='string', port=int}

```


