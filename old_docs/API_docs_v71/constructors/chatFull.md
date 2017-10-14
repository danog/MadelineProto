---
title: chatFull
description: chatFull attributes, type and example
---
## Constructor: chatFull  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|---------------|----------|
|id|[int](../types/int.md) | Yes|
|participants|[ChatParticipants](../types/ChatParticipants.md) | Yes|
|chat\_photo|[Photo](../types/Photo.md) | Yes|
|notify\_settings|[PeerNotifySettings](../types/PeerNotifySettings.md) | Yes|
|exported\_invite|[ExportedChatInvite](../types/ExportedChatInvite.md) | Yes|
|bot\_info|Array of [BotInfo](../types/BotInfo.md) | Yes|



### Type: [ChatFull](../types/ChatFull.md)


### Example:

```
$chatFull = ['_' => 'chatFull', 'id' => int, 'participants' => ChatParticipants, 'chat_photo' => Photo, 'notify_settings' => PeerNotifySettings, 'exported_invite' => ExportedChatInvite, 'bot_info' => [BotInfo]];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "chatFull", "id": int, "participants": ChatParticipants, "chat_photo": Photo, "notify_settings": PeerNotifySettings, "exported_invite": ExportedChatInvite, "bot_info": [BotInfo]}
```


Or, if you're into Lua:  


```
chatFull={_='chatFull', id=int, participants=ChatParticipants, chat_photo=Photo, notify_settings=PeerNotifySettings, exported_invite=ExportedChatInvite, bot_info={BotInfo}}

```


