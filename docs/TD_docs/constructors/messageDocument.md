---
title: messageDocument
description: Document message
---
## Constructor: messageDocument  
[Back to constructors index](index.md)



Document message

### Attributes:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|document|[document](../types/document.md) | Yes|Message content|
|caption|[string](../types/string.md) | Yes|Document caption|



### Type: [MessageContent](../types/MessageContent.md)


### Example:

```
$messageDocument = ['_' => 'messageDocument', 'document' => document, 'caption' => 'string'];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "messageDocument", "document": document, "caption": "string"}
```


Or, if you're into Lua:  


```
messageDocument={_='messageDocument', document=document, caption='string'}

```


