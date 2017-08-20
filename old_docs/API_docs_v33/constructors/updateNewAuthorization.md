---
title: updateNewAuthorization
description: updateNewAuthorization attributes, type and example
---
## Constructor: updateNewAuthorization  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|---------------|----------|
|auth\_key\_id|[long](../types/long.md) | Yes|
|date|[int](../types/int.md) | Yes|
|device|[string](../types/string.md) | Yes|
|location|[string](../types/string.md) | Yes|



### Type: [Update](../types/Update.md)


### Example:

```
$updateNewAuthorization = ['_' => 'updateNewAuthorization', 'auth_key_id' => long, 'date' => int, 'device' => 'string', 'location' => 'string'];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "updateNewAuthorization", "auth_key_id": long, "date": int, "device": "string", "location": "string"}
```


Or, if you're into Lua:  


```
updateNewAuthorization={_='updateNewAuthorization', auth_key_id=long, date=int, device='string', location='string'}

```


