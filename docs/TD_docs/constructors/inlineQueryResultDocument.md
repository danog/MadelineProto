---
title: inlineQueryResultDocument
description: Represents a document cached on the telegram server
---
## Constructor: inlineQueryResultDocument  
[Back to constructors index](index.md)



Represents a document cached on the telegram server

### Attributes:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|id|[string](../types/string.md) | Yes|Unique identifier of this result|
|document|[document](../types/document.md) | Yes|The document|
|title|[string](../types/string.md) | Yes|Document title|
|description|[string](../types/string.md) | Yes|Document description|



### Type: [InlineQueryResult](../types/InlineQueryResult.md)


### Example:

```
$inlineQueryResultDocument = ['_' => 'inlineQueryResultDocument', 'id' => 'string', 'document' => document, 'title' => 'string', 'description' => 'string'];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "inlineQueryResultDocument", "id": "string", "document": document, "title": "string", "description": "string"}
```


Or, if you're into Lua:  


```
inlineQueryResultDocument={_='inlineQueryResultDocument', id='string', document=document, title='string', description='string'}

```


