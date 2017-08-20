---
title: config
description: config attributes, type and example
---
## Constructor: config  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|---------------|----------|
|date|[int](../types/int.md) | Yes|
|test\_mode|[Bool](../types/Bool.md) | Yes|
|this\_dc|[int](../types/int.md) | Yes|
|dc\_options|Array of [DcOption](../types/DcOption.md) | Yes|
|chat\_size\_max|[int](../types/int.md) | Yes|
|broadcast\_size\_max|[int](../types/int.md) | Yes|



### Type: [Config](../types/Config.md)


### Example:

```
$config = ['_' => 'config', 'date' => int, 'test_mode' => Bool, 'this_dc' => int, 'dc_options' => [DcOption], 'chat_size_max' => int, 'broadcast_size_max' => int];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "config", "date": int, "test_mode": Bool, "this_dc": int, "dc_options": [DcOption], "chat_size_max": int, "broadcast_size_max": int}
```


Or, if you're into Lua:  


```
config={_='config', date=int, test_mode=Bool, this_dc=int, dc_options={DcOption}, chat_size_max=int, broadcast_size_max=int}

```


