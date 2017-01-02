---
title: channelFull
description: channelFull attributes, type and example
---
## Constructor: channelFull  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|:-------------:|---------:|
|id|[int](../types/int.md) | Required|
|read\_inbox\_max\_id|[int](../types/int.md) | Required|
|unread\_count|[int](../types/int.md) | Required|
|unread\_important\_count|[int](../types/int.md) | Required|
|chat\_photo|[Photo](../types/Photo.md) | Required|
|notify\_settings|[PeerNotifySettings](../types/PeerNotifySettings.md) | Required|
|exported\_invite|[ExportedChatInvite](../types/ExportedChatInvite.md) | Required|



### Type: [ChatFull](../types/ChatFull.md)


### Example:

```
$channelFull = ['_' => 'channelFull', 'id' => int, 'read_inbox_max_id' => int, 'unread_count' => int, 'unread_important_count' => int, 'chat_photo' => Photo, 'notify_settings' => PeerNotifySettings, 'exported_invite' => ExportedChatInvite, ];
```  

