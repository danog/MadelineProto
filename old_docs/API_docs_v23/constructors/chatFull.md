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



### Type: [ChatFull](../types/ChatFull.md)


### Example:

```
$chatFull = ['_' => 'chatFull', 'id' => int, 'participants' => ChatParticipants, 'chat_photo' => Photo, 'notify_settings' => PeerNotifySettings];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "chatFull", "id": int, "participants": ChatParticipants, "chat_photo": Photo, "notify_settings": PeerNotifySettings}
```


Or, if you're into Lua:  


```
chatFull={_='chatFull', id=int, participants=ChatParticipants, chat_photo=Photo, notify_settings=PeerNotifySettings}

```


