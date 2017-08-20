---
title: webDocument
description: webDocument attributes, type and example
---
## Constructor: webDocument  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|---------------|----------|
|url|[string](../types/string.md) | Yes|
|access\_hash|[long](../types/long.md) | Yes|
|size|[int](../types/int.md) | Yes|
|mime\_type|[string](../types/string.md) | Yes|
|attributes|Array of [DocumentAttribute](../types/DocumentAttribute.md) | Yes|
|dc\_id|[int](../types/int.md) | Yes|



### Type: [WebDocument](../types/WebDocument.md)


### Example:

```
$webDocument = ['_' => 'webDocument', 'url' => 'string', 'access_hash' => long, 'size' => int, 'mime_type' => 'string', 'attributes' => [DocumentAttribute], 'dc_id' => int];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "webDocument", "url": "string", "access_hash": long, "size": int, "mime_type": "string", "attributes": [DocumentAttribute], "dc_id": int}
```


Or, if you're into Lua:  


```
webDocument={_='webDocument', url='string', access_hash=long, size=int, mime_type='string', attributes={DocumentAttribute}, dc_id=int}

```


