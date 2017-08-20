---
title: inputMediaDocumentExternal
description: inputMediaDocumentExternal attributes, type and example
---
## Constructor: inputMediaDocumentExternal  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|---------------|----------|
|url|[string](../types/string.md) | Yes|
|caption|[string](../types/string.md) | Yes|
|ttl\_seconds|[int](../types/int.md) | Optional|



### Type: [InputMedia](../types/InputMedia.md)


### Example:

```
$inputMediaDocumentExternal = ['_' => 'inputMediaDocumentExternal', 'url' => 'string', 'caption' => 'string', 'ttl_seconds' => int];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "inputMediaDocumentExternal", "url": "string", "caption": "string", "ttl_seconds": int}
```


Or, if you're into Lua:  


```
inputMediaDocumentExternal={_='inputMediaDocumentExternal', url='string', caption='string', ttl_seconds=int}

```


