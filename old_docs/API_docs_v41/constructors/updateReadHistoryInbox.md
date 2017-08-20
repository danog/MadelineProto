---
title: updateReadHistoryInbox
description: updateReadHistoryInbox attributes, type and example
---
## Constructor: updateReadHistoryInbox  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|---------------|----------|
|peer|[Peer](../types/Peer.md) | Yes|
|max\_id|[int](../types/int.md) | Yes|
|pts|[int](../types/int.md) | Yes|
|pts\_count|[int](../types/int.md) | Yes|



### Type: [Update](../types/Update.md)


### Example:

```
$updateReadHistoryInbox = ['_' => 'updateReadHistoryInbox', 'peer' => Peer, 'max_id' => int, 'pts' => int, 'pts_count' => int];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "updateReadHistoryInbox", "peer": Peer, "max_id": int, "pts": int, "pts_count": int}
```


Or, if you're into Lua:  


```
updateReadHistoryInbox={_='updateReadHistoryInbox', peer=Peer, max_id=int, pts=int, pts_count=int}

```


