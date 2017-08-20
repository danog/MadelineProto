---
title: contactStatus
description: contactStatus attributes, type and example
---
## Constructor: contactStatus  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|---------------|----------|
|user\_id|[int](../types/int.md) | Yes|
|expires|[int](../types/int.md) | Yes|



### Type: [ContactStatus](../types/ContactStatus.md)


### Example:

```
$contactStatus = ['_' => 'contactStatus', 'user_id' => int, 'expires' => int];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "contactStatus", "user_id": int, "expires": int}
```


Or, if you're into Lua:  


```
contactStatus={_='contactStatus', user_id=int, expires=int}

```


