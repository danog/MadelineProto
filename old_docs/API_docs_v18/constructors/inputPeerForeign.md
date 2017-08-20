---
title: inputPeerForeign
description: inputPeerForeign attributes, type and example
---
## Constructor: inputPeerForeign  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|---------------|----------|
|user\_id|[int](../types/int.md) | Yes|
|access\_hash|[long](../types/long.md) | Yes|



### Type: [InputPeer](../types/InputPeer.md)


### Example:

```
$inputPeerForeign = ['_' => 'inputPeerForeign', 'user_id' => int, 'access_hash' => long];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "inputPeerForeign", "user_id": int, "access_hash": long}
```


Or, if you're into Lua:  


```
inputPeerForeign={_='inputPeerForeign', user_id=int, access_hash=long}

```


