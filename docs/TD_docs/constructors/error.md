---
title: error
description: Object of this type may be returned on every function call in case of the error
---
## Constructor: error  
[Back to constructors index](index.md)



Object of this type may be returned on every function call in case of the error

### Attributes:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|code|[int](../types/int.md) | Yes|Error code, maybe changed in the future|
|message|[string](../types/string.md) | Yes|Error message, may be changed in the future|



### Type: [Error](../types/Error.md)


### Example:

```
$error = ['_' => 'error', 'code' => int, 'message' => 'string'];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "error", "code": int, "message": "string"}
```


Or, if you're into Lua:  


```
error={_='error', code=int, message='string'}

```


