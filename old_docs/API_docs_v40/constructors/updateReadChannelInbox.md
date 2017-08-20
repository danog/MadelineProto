---
title: updateReadChannelInbox
description: updateReadChannelInbox attributes, type and example
---
## Constructor: updateReadChannelInbox  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|---------------|----------|
|peer|[Peer](../types/Peer.md) | Yes|
|max\_id|[int](../types/int.md) | Yes|



### Type: [Update](../types/Update.md)


### Example:

```
$updateReadChannelInbox = ['_' => 'updateReadChannelInbox', 'peer' => Peer, 'max_id' => int];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "updateReadChannelInbox", "peer": Peer, "max_id": int}
```


Or, if you're into Lua:  


```
updateReadChannelInbox={_='updateReadChannelInbox', peer=Peer, max_id=int}

```


