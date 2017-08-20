---
title: authCodeTypeCall
description: Code is delievered by voice call to the specified phone number
---
## Constructor: authCodeTypeCall  
[Back to constructors index](index.md)



Code is delievered by voice call to the specified phone number

### Attributes:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|length|[int](../types/int.md) | Yes|Length of the code|



### Type: [AuthCodeType](../types/AuthCodeType.md)


### Example:

```
$authCodeTypeCall = ['_' => 'authCodeTypeCall', 'length' => int];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "authCodeTypeCall", "length": int}
```


Or, if you're into Lua:  


```
authCodeTypeCall={_='authCodeTypeCall', length=int}

```


