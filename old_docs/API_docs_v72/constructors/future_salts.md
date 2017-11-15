---
title: future_salts
description: future_salts attributes, type and example
---
## Constructor: future\_salts  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|---------------|----------|
|req\_msg\_id|[long](../types/long.md) | Yes|
|now|[int](../types/int.md) | Yes|
|salts|Array of [future\_salt](../constructors/future_salt.md) | Yes|



### Type: [FutureSalts](../types/FutureSalts.md)


### Example:

```
$future_salts = ['_' => 'future_salts', 'req_msg_id' => long, 'now' => int, 'salts' => [future_salt]];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "future_salts", "req_msg_id": long, "now": int, "salts": [future_salt]}
```


Or, if you're into Lua:  


```
future_salts={_='future_salts', req_msg_id=long, now=int, salts={future_salt}}

```


