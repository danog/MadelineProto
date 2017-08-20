---
title: pagePart
description: pagePart attributes, type and example
---
## Constructor: pagePart  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|---------------|----------|
|blocks|Array of [PageBlock](../types/PageBlock.md) | Yes|
|photos|Array of [Photo](../types/Photo.md) | Yes|
|documents|Array of [Document](../types/Document.md) | Yes|



### Type: [Page](../types/Page.md)


### Example:

```
$pagePart = ['_' => 'pagePart', 'blocks' => [PageBlock], 'photos' => [Photo], 'documents' => [Document]];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "pagePart", "blocks": [PageBlock], "photos": [Photo], "documents": [Document]}
```


Or, if you're into Lua:  


```
pagePart={_='pagePart', blocks={PageBlock}, photos={Photo}, documents={Document}}

```


