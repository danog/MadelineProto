---
title: optionInteger
description: Integer option
---
## Constructor: optionInteger  
[Back to constructors index](index.md)



Integer option

### Attributes:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|value|[int](../types/int.md) | Yes|Value of an option|



### Type: [OptionValue](../types/OptionValue.md)


### Example:

```
$optionInteger = ['_' => 'optionInteger', 'value' => int];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "optionInteger", "value": int}
```


Or, if you're into Lua:  


```
optionInteger={_='optionInteger', value=int}

```


