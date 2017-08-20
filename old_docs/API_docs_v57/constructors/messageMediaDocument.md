---
title: messageMediaDocument
description: messageMediaDocument attributes, type and example
---
## Constructor: messageMediaDocument  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|---------------|----------|
|document|[Document](../types/Document.md) | Yes|
|caption|[string](../types/string.md) | Yes|



### Type: [MessageMedia](../types/MessageMedia.md)


### Example:

```
$messageMediaDocument = ['_' => 'messageMediaDocument', 'document' => Document, 'caption' => 'string'];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "messageMediaDocument", "document": Document, "caption": "string"}
```


Or, if you're into Lua:  


```
messageMediaDocument={_='messageMediaDocument', document=Document, caption='string'}

```


