---
title: disabledFeature
description: disabledFeature attributes, type and example
---
## Constructor: disabledFeature  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|---------------|----------|
|feature|[string](../types/string.md) | Yes|
|description|[string](../types/string.md) | Yes|



### Type: [DisabledFeature](../types/DisabledFeature.md)


### Example:

```
$disabledFeature = ['_' => 'disabledFeature', 'feature' => 'string', 'description' => 'string'];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "disabledFeature", "feature": "string", "description": "string"}
```


Or, if you're into Lua:  


```
disabledFeature={_='disabledFeature', feature='string', description='string'}

```


