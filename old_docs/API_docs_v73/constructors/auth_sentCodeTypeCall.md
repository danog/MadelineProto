---
title: auth.sentCodeTypeCall
description: auth_sentCodeTypeCall attributes, type and example
---
## Constructor: auth.sentCodeTypeCall  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|---------------|----------|
|length|[int](../types/int.md) | Yes|



### Type: [auth\_SentCodeType](../types/auth_SentCodeType.md)


### Example:

```
$auth_sentCodeTypeCall = ['_' => 'auth.sentCodeTypeCall', 'length' => int];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "auth.sentCodeTypeCall", "length": int}
```


Or, if you're into Lua:  


```
auth_sentCodeTypeCall={_='auth.sentCodeTypeCall', length=int}

```


