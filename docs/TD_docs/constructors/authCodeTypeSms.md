---
title: authCodeTypeSms
description: Code is delivered by SMS to the specified phone number
---
## Constructor: authCodeTypeSms  
[Back to constructors index](index.md)



Code is delivered by SMS to the specified phone number

### Attributes:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|length|[int](../types/int.md) | Yes|Length of the code|



### Type: [AuthCodeType](../types/AuthCodeType.md)


### Example:

```
$authCodeTypeSms = ['_' => 'authCodeTypeSms', 'length' => int];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "authCodeTypeSms", "length": int}
```


Or, if you're into Lua:  


```
authCodeTypeSms={_='authCodeTypeSms', length=int}

```


