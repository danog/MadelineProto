---
title: inputMediaDocument
description: inputMediaDocument attributes, type and example
---
## Constructor: inputMediaDocument  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|---------------|----------|
|id|[MessageMedia, Message, Update or InputDocument](../types/InputDocument.md) | Optional|



### Type: [InputMedia](../types/InputMedia.md)


### Example:

```
$inputMediaDocument = ['_' => 'inputMediaDocument', 'id' => InputDocument];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "inputMediaDocument", "id": InputDocument}
```


Or, if you're into Lua:  


```
inputMediaDocument={_='inputMediaDocument', id=InputDocument}

```


