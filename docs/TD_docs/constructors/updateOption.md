---
title: updateOption
description: Some option changed its value
---
## Constructor: updateOption  
[Back to constructors index](index.md)



Some option changed its value

### Attributes:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|name|[string](../types/string.md) | Yes|Option name|
|value|[OptionValue](../types/OptionValue.md) | Yes|New option value|



### Type: [Update](../types/Update.md)


### Example:

```
$updateOption = ['_' => 'updateOption', 'name' => 'string', 'value' => OptionValue];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "updateOption", "name": "string", "value": OptionValue}
```


Or, if you're into Lua:  


```
updateOption={_='updateOption', name='string', value=OptionValue}

```


