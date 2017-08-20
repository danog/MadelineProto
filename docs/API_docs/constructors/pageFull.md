---
title: pageFull
description: pageFull attributes, type and example
---
## Constructor: pageFull  
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
$pageFull = ['_' => 'pageFull', 'blocks' => [PageBlock], 'photos' => [Photo], 'documents' => [Document]];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "pageFull", "blocks": [PageBlock], "photos": [Photo], "documents": [Document]}
```


Or, if you're into Lua:  


```
pageFull={_='pageFull', blocks={PageBlock}, photos={Photo}, documents={Document}}

```


