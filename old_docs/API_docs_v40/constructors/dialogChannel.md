---
title: dialogChannel
description: dialogChannel attributes, type and example
---
## Constructor: dialogChannel  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|---------------|----------|
|peer|[Peer](../types/Peer.md) | Yes|
|top\_message|[int](../types/int.md) | Yes|
|top\_important\_message|[int](../types/int.md) | Yes|
|read\_inbox\_max\_id|[int](../types/int.md) | Yes|
|unread\_count|[int](../types/int.md) | Yes|
|unread\_important\_count|[int](../types/int.md) | Yes|
|notify\_settings|[PeerNotifySettings](../types/PeerNotifySettings.md) | Yes|
|pts|[int](../types/int.md) | Yes|



### Type: [Dialog](../types/Dialog.md)


### Example:

```
$dialogChannel = ['_' => 'dialogChannel', 'peer' => Peer, 'top_message' => int, 'top_important_message' => int, 'read_inbox_max_id' => int, 'unread_count' => int, 'unread_important_count' => int, 'notify_settings' => PeerNotifySettings, 'pts' => int];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "dialogChannel", "peer": Peer, "top_message": int, "top_important_message": int, "read_inbox_max_id": int, "unread_count": int, "unread_important_count": int, "notify_settings": PeerNotifySettings, "pts": int}
```


Or, if you're into Lua:  


```
dialogChannel={_='dialogChannel', peer=Peer, top_message=int, top_important_message=int, read_inbox_max_id=int, unread_count=int, unread_important_count=int, notify_settings=PeerNotifySettings, pts=int}

```


