---
title: updateContactRegistered
description: updateContactRegistered attributes, type and example
---
## Constructor: updateContactRegistered  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|---------------|----------|
|user\_id|[int](../types/int.md) | Yes|
|date|[int](../types/int.md) | Yes|



### Type: [Update](../types/Update.md)


### Example:

```
$updateContactRegistered = ['_' => 'updateContactRegistered', 'user_id' => int, 'date' => int];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "updateContactRegistered", "user_id": int, "date": int}
```


Or, if you're into Lua:  


```
updateContactRegistered={_='updateContactRegistered', user_id=int, date=int}

```


