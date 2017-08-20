---
title: langPackDifference
description: langPackDifference attributes, type and example
---
## Constructor: langPackDifference  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|---------------|----------|
|lang\_code|[string](../types/string.md) | Yes|
|from\_version|[int](../types/int.md) | Yes|
|version|[int](../types/int.md) | Yes|
|strings|Array of [LangPackString](../types/LangPackString.md) | Yes|



### Type: [LangPackDifference](../types/LangPackDifference.md)


### Example:

```
$langPackDifference = ['_' => 'langPackDifference', 'lang_code' => 'string', 'from_version' => int, 'version' => int, 'strings' => [LangPackString]];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "langPackDifference", "lang_code": "string", "from_version": int, "version": int, "strings": [LangPackString]}
```


Or, if you're into Lua:  


```
langPackDifference={_='langPackDifference', lang_code='string', from_version=int, version=int, strings={LangPackString}}

```


