---
title: http_wait
description: http_wait attributes, type and example
---
## Constructor: http\_wait  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|---------------|----------|
|max\_delay|[int](../types/int.md) | Yes|
|wait\_after|[int](../types/int.md) | Yes|
|max\_wait|[int](../types/int.md) | Yes|



### Type: [HttpWait](../types/HttpWait.md)


### Example:

```
$http_wait = ['_' => 'http_wait', 'max_delay' => int, 'wait_after' => int, 'max_wait' => int];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "http_wait", "max_delay": int, "wait_after": int, "max_wait": int}
```


Or, if you're into Lua:  


```
http_wait={_='http_wait', max_delay=int, wait_after=int, max_wait=int}

```


