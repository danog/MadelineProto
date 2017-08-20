---
title: channelFull
description: channelFull attributes, type and example
---
## Constructor: channelFull  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|---------------|----------|
|id|[int](../types/int.md) | Yes|
|about|[string](../types/string.md) | Yes|
|participants\_count|[int](../types/int.md) | Optional|
|admins\_count|[int](../types/int.md) | Optional|
|kicked\_count|[int](../types/int.md) | Optional|
|read\_inbox\_max\_id|[int](../types/int.md) | Yes|
|unread\_count|[int](../types/int.md) | Yes|
|unread\_important\_count|[int](../types/int.md) | Yes|
|chat\_photo|[Photo](../types/Photo.md) | Yes|
|notify\_settings|[PeerNotifySettings](../types/PeerNotifySettings.md) | Yes|
|exported\_invite|[ExportedChatInvite](../types/ExportedChatInvite.md) | Yes|



### Type: [ChatFull](../types/ChatFull.md)


### Example:

```
$channelFull = ['_' => 'channelFull', 'id' => int, 'about' => 'string', 'participants_count' => int, 'admins_count' => int, 'kicked_count' => int, 'read_inbox_max_id' => int, 'unread_count' => int, 'unread_important_count' => int, 'chat_photo' => Photo, 'notify_settings' => PeerNotifySettings, 'exported_invite' => ExportedChatInvite];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "channelFull", "id": int, "about": "string", "participants_count": int, "admins_count": int, "kicked_count": int, "read_inbox_max_id": int, "unread_count": int, "unread_important_count": int, "chat_photo": Photo, "notify_settings": PeerNotifySettings, "exported_invite": ExportedChatInvite}
```


Or, if you're into Lua:  


```
channelFull={_='channelFull', id=int, about='string', participants_count=int, admins_count=int, kicked_count=int, read_inbox_max_id=int, unread_count=int, unread_important_count=int, chat_photo=Photo, notify_settings=PeerNotifySettings, exported_invite=ExportedChatInvite}

```


