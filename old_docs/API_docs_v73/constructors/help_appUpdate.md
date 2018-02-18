---
title: help.appUpdate
description: help_appUpdate attributes, type and example
---
## Constructor: help.appUpdate  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|---------------|----------|
|id|[int](../types/int.md) | Yes|
|critical|[Bool](../types/Bool.md) | Yes|
|url|[string](../types/string.md) | Yes|
|text|[string](../types/string.md) | Yes|



### Type: [help\_AppUpdate](../types/help_AppUpdate.md)


### Example:

```
$help_appUpdate = ['_' => 'help.appUpdate', 'id' => int, 'critical' => Bool, 'url' => 'string', 'text' => 'string'];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "help.appUpdate", "id": int, "critical": Bool, "url": "string", "text": "string"}
```


Or, if you're into Lua:  


```
help_appUpdate={_='help.appUpdate', id=int, critical=Bool, url='string', text='string'}

```


