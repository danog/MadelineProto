---
title: inputPeerChannel
description: inputPeerChannel attributes, type and example
---
## Constructor: inputPeerChannel  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|---------------|----------|
|channel\_id|[int](../types/int.md) | Yes|
|access\_hash|[long](../types/long.md) | Yes|



### Type: [InputPeer](../types/InputPeer.md)


### Example:

```
$inputPeerChannel = ['_' => 'inputPeerChannel', 'channel_id' => int, 'access_hash' => long];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "inputPeerChannel", "channel_id": int, "access_hash": long}
```


Or, if you're into Lua:  


```
inputPeerChannel={_='inputPeerChannel', channel_id=int, access_hash=long}

```


