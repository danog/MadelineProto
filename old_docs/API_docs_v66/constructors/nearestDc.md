---
title: nearestDc
description: nearestDc attributes, type and example
---
## Constructor: nearestDc  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|---------------|----------|
|country|[string](../types/string.md) | Yes|
|this\_dc|[int](../types/int.md) | Yes|
|nearest\_dc|[int](../types/int.md) | Yes|



### Type: [NearestDc](../types/NearestDc.md)


### Example:

```
$nearestDc = ['_' => 'nearestDc', 'country' => 'string', 'this_dc' => int, 'nearest_dc' => int];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "nearestDc", "country": "string", "this_dc": int, "nearest_dc": int}
```


Or, if you're into Lua:  


```
nearestDc={_='nearestDc', country='string', this_dc=int, nearest_dc=int}

```


