---
title: inputDocumentFileLocation
description: inputDocumentFileLocation attributes, type and example
---
## Constructor: inputDocumentFileLocation  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|---------------|----------|
|id|[long](../types/long.md) | Yes|
|access\_hash|[long](../types/long.md) | Yes|
|version|[int](../types/int.md) | Yes|



### Type: [InputFileLocation](../types/InputFileLocation.md)


### Example:

```
$inputDocumentFileLocation = ['_' => 'inputDocumentFileLocation', 'id' => long, 'access_hash' => long, 'version' => int];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "inputDocumentFileLocation", "id": long, "access_hash": long, "version": int}
```


Or, if you're into Lua:  


```
inputDocumentFileLocation={_='inputDocumentFileLocation', id=long, access_hash=long, version=int}

```


