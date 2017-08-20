---
title: contactBlocked
description: contactBlocked attributes, type and example
---
## Constructor: contactBlocked  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|---------------|----------|
|user\_id|[int](../types/int.md) | Yes|
|date|[int](../types/int.md) | Yes|



### Type: [ContactBlocked](../types/ContactBlocked.md)


### Example:

```
$contactBlocked = ['_' => 'contactBlocked', 'user_id' => int, 'date' => int];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "contactBlocked", "user_id": int, "date": int}
```


Or, if you're into Lua:  


```
contactBlocked={_='contactBlocked', user_id=int, date=int}

```


