---
title: inputEncryptedFile
description: inputEncryptedFile attributes, type and example
---
## Constructor: inputEncryptedFile  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|---------------|----------|
|id|[long](../types/long.md) | Yes|
|access\_hash|[long](../types/long.md) | Yes|



### Type: [InputEncryptedFile](../types/InputEncryptedFile.md)


### Example:

```
$inputEncryptedFile = ['_' => 'inputEncryptedFile', 'id' => long, 'access_hash' => long];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "inputEncryptedFile", "id": long, "access_hash": long}
```


Or, if you're into Lua:  


```
inputEncryptedFile={_='inputEncryptedFile', id=long, access_hash=long}

```


