---
title: inputWebDocument
description: inputWebDocument attributes, type and example
---
## Constructor: inputWebDocument  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|---------------|----------|
|url|[string](../types/string.md) | Yes|
|size|[int](../types/int.md) | Yes|
|mime\_type|[string](../types/string.md) | Yes|
|attributes|Array of [DocumentAttribute](../types/DocumentAttribute.md) | Yes|



### Type: [InputWebDocument](../types/InputWebDocument.md)


### Example:

```
$inputWebDocument = ['_' => 'inputWebDocument', 'url' => 'string', 'size' => int, 'mime_type' => 'string', 'attributes' => [DocumentAttribute]];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "inputWebDocument", "url": "string", "size": int, "mime_type": "string", "attributes": [DocumentAttribute]}
```


Or, if you're into Lua:  


```
inputWebDocument={_='inputWebDocument', url='string', size=int, mime_type='string', attributes={DocumentAttribute}}

```


