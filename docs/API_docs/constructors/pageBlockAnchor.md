---
title: pageBlockAnchor
description: Invisible anchor on a page which can be used in a URL to open a page from the specified anchor
---
## Constructor: pageBlockAnchor  
[Back to constructors index](index.md)



Invisible anchor on a page which can be used in a URL to open a page from the specified anchor

### Attributes:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|name|[string](../types/string.md) | Yes|Name of the anchor|



### Type: [PageBlock](../types/PageBlock.md)


### Example:

```
$pageBlockAnchor = ['_' => 'pageBlockAnchor', 'name' => 'string'];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "pageBlockAnchor", "name": "string"}
```


Or, if you're into Lua:  


```
pageBlockAnchor={_='pageBlockAnchor', name='string'}

```


