---
title: messageMediaDocument
description: messageMediaDocument attributes, type and example
---
## Constructor: messageMediaDocument  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|---------------|----------|
|document|[Document](../types/Document.md) | Optional|
|caption|[string](../types/string.md) | Optional|
|ttl\_seconds|[int](../types/int.md) | Optional|



### Type: [MessageMedia](../types/MessageMedia.md)


### Example:

```
$messageMediaDocument = ['_' => 'messageMediaDocument', 'document' => Document, 'caption' => 'string', 'ttl_seconds' => int];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "messageMediaDocument", "document": Document, "caption": "string", "ttl_seconds": int}
```


Or, if you're into Lua:  


```
messageMediaDocument={_='messageMediaDocument', document=Document, caption='string', ttl_seconds=int}

```


