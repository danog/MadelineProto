---
title: inputGeoChat
description: inputGeoChat attributes, type and example
---
## Constructor: inputGeoChat  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|---------------|----------|
|chat\_id|[int](../types/int.md) | Yes|
|access\_hash|[long](../types/long.md) | Yes|



### Type: [InputGeoChat](../types/InputGeoChat.md)


### Example:

```
$inputGeoChat = ['_' => 'inputGeoChat', 'chat_id' => int, 'access_hash' => long];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "inputGeoChat", "chat_id": int, "access_hash": long}
```


Or, if you're into Lua:  


```
inputGeoChat={_='inputGeoChat', chat_id=int, access_hash=long}

```


