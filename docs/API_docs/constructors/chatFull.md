## Constructor: chatFull  

### Attributes:

| Name     |    Type       | Required |
|----------|:-------------:|---------:|
|id|[int](../types/int.md) | Required|
|participants|[ChatParticipants](../types/ChatParticipants.md) | Required|
|chat\_photo|[Photo](../types/Photo.md) | Required|
|notify\_settings|[PeerNotifySettings](../types/PeerNotifySettings.md) | Required|
|exported\_invite|[ExportedChatInvite](../types/ExportedChatInvite.md) | Required|
|bot\_info|Array of [BotInfo](../types/BotInfo.md) | Required|
### Type: 

[ChatFull](../types/ChatFull.md)
### Example:

```
$chatFull = ['_' => chatFull', 'id' => int, 'participants' => ChatParticipants, 'chat_photo' => Photo, 'notify_settings' => PeerNotifySettings, 'exported_invite' => ExportedChatInvite, 'bot_info' => [Vector t], ];
```