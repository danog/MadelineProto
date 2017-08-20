---
title: inputEncryptedFileLocation
description: inputEncryptedFileLocation attributes, type and example
---
## Constructor: inputEncryptedFileLocation  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|---------------|----------|
|id|[long](../types/long.md) | Yes|
|access\_hash|[long](../types/long.md) | Yes|



### Type: [InputFileLocation](../types/InputFileLocation.md)


### Example:

```
$inputEncryptedFileLocation = ['_' => 'inputEncryptedFileLocation', 'id' => long, 'access_hash' => long];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "inputEncryptedFileLocation", "id": long, "access_hash": long}
```


Or, if you're into Lua:  


```
inputEncryptedFileLocation={_='inputEncryptedFileLocation', id=long, access_hash=long}

```


