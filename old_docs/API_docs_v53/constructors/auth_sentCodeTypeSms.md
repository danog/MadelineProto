---
title: auth.sentCodeTypeSms
description: auth_sentCodeTypeSms attributes, type and example
---
## Constructor: auth.sentCodeTypeSms  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|---------------|----------|
|length|[int](../types/int.md) | Yes|



### Type: [auth\_SentCodeType](../types/auth_SentCodeType.md)


### Example:

```
$auth_sentCodeTypeSms = ['_' => 'auth.sentCodeTypeSms', 'length' => int];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "auth.sentCodeTypeSms", "length": int}
```


Or, if you're into Lua:  


```
auth_sentCodeTypeSms={_='auth.sentCodeTypeSms', length=int}

```


