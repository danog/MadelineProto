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



### Type: [InputMedia](../types/InputMedia.md)


### Example:

```
$inputMediaDocument = ['_' => 'inputMediaDocument', 'id' => InputDocument, 'caption' => 'string'];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "inputMediaDocument", "id": InputDocument, "caption": "string"}
```


Or, if you're into Lua:  


```
inputMediaDocument={_='inputMediaDocument', id=InputDocument, caption='string'}

```


