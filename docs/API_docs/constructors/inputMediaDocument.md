---
title: inputMediaDocument
description: inputMediaDocument attributes, type and example
---
## Constructor: inputMediaDocument  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|---------------|----------|
|id|[InputDocument](../types/InputDocument.md) | Yes|
|caption|[string](../types/string.md) | Yes|
|ttl\_seconds|[int](../types/int.md) | Optional|



### Type: [InputMedia](../types/InputMedia.md)


### Example:

```
$inputMediaDocument = ['_' => 'inputMediaDocument', 'id' => InputDocument, 'caption' => 'string', 'ttl_seconds' => int];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "inputMediaDocument", "id": InputDocument, "caption": "string", "ttl_seconds": int}
```


Or, if you're into Lua:  


```
inputMediaDocument={_='inputMediaDocument', id=InputDocument, caption='string', ttl_seconds=int}

```


