---
title: dcOption
description: dcOption attributes, type and example
---
## Constructor: dcOption  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|---------------|----------|
|ipv6|[Bool](../types/Bool.md) | Optional|
|media\_only|[Bool](../types/Bool.md) | Optional|
|tcpo\_only|[Bool](../types/Bool.md) | Optional|
|cdn|[Bool](../types/Bool.md) | Optional|
|static|[Bool](../types/Bool.md) | Optional|
|id|[int](../types/int.md) | Yes|
|ip\_address|[string](../types/string.md) | Yes|
|port|[int](../types/int.md) | Yes|



### Type: [DcOption](../types/DcOption.md)


### Example:

```
$dcOption = ['_' => 'dcOption', 'ipv6' => Bool, 'media_only' => Bool, 'tcpo_only' => Bool, 'cdn' => Bool, 'static' => Bool, 'id' => int, 'ip_address' => 'string', 'port' => int];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "dcOption", "ipv6": Bool, "media_only": Bool, "tcpo_only": Bool, "cdn": Bool, "static": Bool, "id": int, "ip_address": "string", "port": int}
```


Or, if you're into Lua:  


```
dcOption={_='dcOption', ipv6=Bool, media_only=Bool, tcpo_only=Bool, cdn=Bool, static=Bool, id=int, ip_address='string', port=int}

```


