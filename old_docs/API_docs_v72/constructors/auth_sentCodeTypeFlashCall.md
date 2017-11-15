---
title: auth.sentCodeTypeFlashCall
description: auth_sentCodeTypeFlashCall attributes, type and example
---
## Constructor: auth.sentCodeTypeFlashCall  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|---------------|----------|
|pattern|[string](../types/string.md) | Yes|



### Type: [auth\_SentCodeType](../types/auth_SentCodeType.md)


### Example:

```
$auth_sentCodeTypeFlashCall = ['_' => 'auth.sentCodeTypeFlashCall', 'pattern' => 'string'];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "auth.sentCodeTypeFlashCall", "pattern": "string"}
```


Or, if you're into Lua:  


```
auth_sentCodeTypeFlashCall={_='auth.sentCodeTypeFlashCall', pattern='string'}

```


