---
title: langPackLanguage
description: langPackLanguage attributes, type and example
---
## Constructor: langPackLanguage  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|---------------|----------|
|name|[string](../types/string.md) | Yes|
|native\_name|[string](../types/string.md) | Yes|
|lang\_code|[string](../types/string.md) | Yes|



### Type: [LangPackLanguage](../types/LangPackLanguage.md)


### Example:

```
$langPackLanguage = ['_' => 'langPackLanguage', 'name' => 'string', 'native_name' => 'string', 'lang_code' => 'string'];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "langPackLanguage", "name": "string", "native_name": "string", "lang_code": "string"}
```


Or, if you're into Lua:  


```
langPackLanguage={_='langPackLanguage', name='string', native_name='string', lang_code='string'}

```


