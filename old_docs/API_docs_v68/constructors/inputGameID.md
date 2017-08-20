---
title: inputGameID
description: inputGameID attributes, type and example
---
## Constructor: inputGameID  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|---------------|----------|
|id|[long](../types/long.md) | Yes|
|access\_hash|[long](../types/long.md) | Yes|



### Type: [InputGame](../types/InputGame.md)


### Example:

```
$inputGameID = ['_' => 'inputGameID', 'id' => long, 'access_hash' => long];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "inputGameID", "id": long, "access_hash": long}
```


Or, if you're into Lua:  


```
inputGameID={_='inputGameID', id=long, access_hash=long}

```


