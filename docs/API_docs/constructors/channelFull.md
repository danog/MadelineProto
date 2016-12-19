## Constructor: channelFull  

### Attributes:

| Name     |    Type       | Required |
|----------|:-------------:|---------:|
|can\_view\_participants|[Bool](../types/Bool.md) | Optional|
|can\_set\_username|[Bool](../types/Bool.md) | Optional|
|id|[int](../types/int.md) | Required|
|about|[string](../types/string.md) | Required|
|participants\_count|[int](../types/int.md) | Optional|
|admins\_count|[int](../types/int.md) | Optional|
|kicked\_count|[int](../types/int.md) | Optional|
|read\_inbox\_max\_id|[int](../types/int.md) | Required|
|read\_outbox\_max\_id|[int](../types/int.md) | Required|
|unread\_count|[int](../types/int.md) | Required|
|chat\_photo|[Photo](../types/Photo.md) | Required|
|notify\_settings|[PeerNotifySettings](../types/PeerNotifySettings.md) | Required|
|exported\_invite|[ExportedChatInvite](../types/ExportedChatInvite.md) | Required|
|bot\_info|Array of [BotInfo](../types/BotInfo.md) | Required|
|migrated\_from\_chat\_id|[int](../types/int.md) | Optional|
|migrated\_from\_max\_id|[int](../types/int.md) | Optional|
|pinned\_msg\_id|[int](../types/int.md) | Optional|
### Type: 

[ChatFull](../types/ChatFull.md)
### Example:

```
$channelFull = ['_' => channelFull', 'can_view_participants' => true, 'can_set_username' => true, 'id' => int, 'about' => string, 'participants_count' => int, 'admins_count' => int, 'kicked_count' => int, 'read_inbox_max_id' => int, 'read_outbox_max_id' => int, 'unread_count' => int, 'chat_photo' => Photo, 'notify_settings' => PeerNotifySettings, 'exported_invite' => ExportedChatInvite, 'bot_info' => [Vector t], 'migrated_from_chat_id' => int, 'migrated_from_max_id' => int, 'pinned_msg_id' => int, ];
```