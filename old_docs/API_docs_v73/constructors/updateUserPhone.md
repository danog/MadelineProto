---
title: updateUserPhone
description: updateUserPhone attributes, type and example
---
## Constructor: updateUserPhone  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|---------------|----------|
|user\_id|[int](../types/int.md) | Yes|
|phone|[string](../types/string.md) | Yes|



### Type: [Update](../types/Update.md)


### Example:

```
$updateUserPhone = ['_' => 'updateUserPhone', 'user_id' => int, 'phone' => 'string'];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "updateUserPhone", "user_id": int, "phone": "string"}
```


Or, if you're into Lua:  


```
updateUserPhone={_='updateUserPhone', user_id=int, phone='string'}

```


