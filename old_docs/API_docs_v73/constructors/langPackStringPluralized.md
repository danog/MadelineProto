---
title: langPackStringPluralized
description: langPackStringPluralized attributes, type and example
---
## Constructor: langPackStringPluralized  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|---------------|----------|
|key|[string](../types/string.md) | Yes|
|zero\_value|[string](../types/string.md) | Optional|
|one\_value|[string](../types/string.md) | Optional|
|two\_value|[string](../types/string.md) | Optional|
|few\_value|[string](../types/string.md) | Optional|
|many\_value|[string](../types/string.md) | Optional|
|other\_value|[string](../types/string.md) | Yes|



### Type: [LangPackString](../types/LangPackString.md)


### Example:

```
$langPackStringPluralized = ['_' => 'langPackStringPluralized', 'key' => 'string', 'zero_value' => 'string', 'one_value' => 'string', 'two_value' => 'string', 'few_value' => 'string', 'many_value' => 'string', 'other_value' => 'string'];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "langPackStringPluralized", "key": "string", "zero_value": "string", "one_value": "string", "two_value": "string", "few_value": "string", "many_value": "string", "other_value": "string"}
```


Or, if you're into Lua:  


```
langPackStringPluralized={_='langPackStringPluralized', key='string', zero_value='string', one_value='string', two_value='string', few_value='string', many_value='string', other_value='string'}

```


