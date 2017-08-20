---
title: dialog
description: dialog attributes, type and example
---
## Constructor: dialog  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|---------------|----------|
|peer|[Peer](../types/Peer.md) | Yes|
|top\_message|[int](../types/int.md) | Yes|
|read\_inbox\_max\_id|[int](../types/int.md) | Yes|
|read\_outbox\_max\_id|[int](../types/int.md) | Yes|
|unread\_count|[int](../types/int.md) | Yes|
|notify\_settings|[PeerNotifySettings](../types/PeerNotifySettings.md) | Yes|
|pts|[int](../types/int.md) | Optional|
|draft|[DraftMessage](../types/DraftMessage.md) | Optional|



### Type: [Dialog](../types/Dialog.md)


### Example:

```
$dialog = ['_' => 'dialog', 'peer' => Peer, 'top_message' => int, 'read_inbox_max_id' => int, 'read_outbox_max_id' => int, 'unread_count' => int, 'notify_settings' => PeerNotifySettings, 'pts' => int, 'draft' => DraftMessage];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "dialog", "peer": Peer, "top_message": int, "read_inbox_max_id": int, "read_outbox_max_id": int, "unread_count": int, "notify_settings": PeerNotifySettings, "pts": int, "draft": DraftMessage}
```


Or, if you're into Lua:  


```
dialog={_='dialog', peer=Peer, top_message=int, read_inbox_max_id=int, read_outbox_max_id=int, unread_count=int, notify_settings=PeerNotifySettings, pts=int, draft=DraftMessage}

```


