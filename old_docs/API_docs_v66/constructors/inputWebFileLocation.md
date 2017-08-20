---
title: inputWebFileLocation
description: inputWebFileLocation attributes, type and example
---
## Constructor: inputWebFileLocation  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|---------------|----------|
|url|[string](../types/string.md) | Yes|
|access\_hash|[long](../types/long.md) | Yes|



### Type: [InputWebFileLocation](../types/InputWebFileLocation.md)


### Example:

```
$inputWebFileLocation = ['_' => 'inputWebFileLocation', 'url' => 'string', 'access_hash' => long];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "inputWebFileLocation", "url": "string", "access_hash": long}
```


Or, if you're into Lua:  


```
inputWebFileLocation={_='inputWebFileLocation', url='string', access_hash=long}

```


