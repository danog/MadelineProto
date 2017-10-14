---
title: channelFull
description: channelFull attributes, type and example
---
## Constructor: channelFull  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|---------------|----------|
|can\_view\_participants|[Bool](../types/Bool.md) | Optional|
|can\_set\_username|[Bool](../types/Bool.md) | Optional|
|can\_set\_stickers|[Bool](../types/Bool.md) | Optional|
|id|[int](../types/int.md) | Yes|
|about|[string](../types/string.md) | Yes|
|participants\_count|[int](../types/int.md) | Optional|
|admins\_count|[int](../types/int.md) | Optional|
|kicked\_count|[int](../types/int.md) | Optional|
|banned\_count|[int](../types/int.md) | Optional|
|read\_inbox\_max\_id|[int](../types/int.md) | Yes|
|read\_outbox\_max\_id|[int](../types/int.md) | Yes|
|unread\_count|[int](../types/int.md) | Yes|
|chat\_photo|[Photo](../types/Photo.md) | Yes|
|notify\_settings|[PeerNotifySettings](../types/PeerNotifySettings.md) | Yes|
|exported\_invite|[ExportedChatInvite](../types/ExportedChatInvite.md) | Yes|
|bot\_info|Array of [BotInfo](../types/BotInfo.md) | Yes|
|migrated\_from\_chat\_id|[int](../types/int.md) | Optional|
|migrated\_from\_max\_id|[int](../types/int.md) | Optional|
|pinned\_msg\_id|[int](../types/int.md) | Optional|
|stickerset|[StickerSet](../types/StickerSet.md) | Optional|



### Type: [ChatFull](../types/ChatFull.md)


### Example:

```
$channelFull = ['_' => 'channelFull', 'can_view_participants' => Bool, 'can_set_username' => Bool, 'can_set_stickers' => Bool, 'id' => int, 'about' => 'string', 'participants_count' => int, 'admins_count' => int, 'kicked_count' => int, 'banned_count' => int, 'read_inbox_max_id' => int, 'read_outbox_max_id' => int, 'unread_count' => int, 'chat_photo' => Photo, 'notify_settings' => PeerNotifySettings, 'exported_invite' => ExportedChatInvite, 'bot_info' => [BotInfo], 'migrated_from_chat_id' => int, 'migrated_from_max_id' => int, 'pinned_msg_id' => int, 'stickerset' => StickerSet];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "channelFull", "can_view_participants": Bool, "can_set_username": Bool, "can_set_stickers": Bool, "id": int, "about": "string", "participants_count": int, "admins_count": int, "kicked_count": int, "banned_count": int, "read_inbox_max_id": int, "read_outbox_max_id": int, "unread_count": int, "chat_photo": Photo, "notify_settings": PeerNotifySettings, "exported_invite": ExportedChatInvite, "bot_info": [BotInfo], "migrated_from_chat_id": int, "migrated_from_max_id": int, "pinned_msg_id": int, "stickerset": StickerSet}
```


Or, if you're into Lua:  


```
channelFull={_='channelFull', can_view_participants=Bool, can_set_username=Bool, can_set_stickers=Bool, id=int, about='string', participants_count=int, admins_count=int, kicked_count=int, banned_count=int, read_inbox_max_id=int, read_outbox_max_id=int, unread_count=int, chat_photo=Photo, notify_settings=PeerNotifySettings, exported_invite=ExportedChatInvite, bot_info={BotInfo}, migrated_from_chat_id=int, migrated_from_max_id=int, pinned_msg_id=int, stickerset=StickerSet}

```


