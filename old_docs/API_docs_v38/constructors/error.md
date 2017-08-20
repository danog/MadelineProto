---
title: error
description: error attributes, type and example
---
## Constructor: error  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|---------------|----------|
|code|[int](../types/int.md) | Yes|
|text|[string](../types/string.md) | Yes|



### Type: [Error](../types/Error.md)


### Example:

```
$error = ['_' => 'error', 'code' => int, 'text' => 'string'];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "error", "code": int, "text": "string"}
```


Or, if you're into Lua:  


```
error={_='error', code=int, text='string'}

```


