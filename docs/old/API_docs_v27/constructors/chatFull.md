---
title: chatFull
description: chatFull attributes, type and example
---
## Constructor: chatFull  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|:-------------:|---------:|
|id|[int](../types/int.md) | Required|
|participants|[ChatParticipants](../types/ChatParticipants.md) | Required|
|chat\_photo|[Photo](../types/Photo.md) | Required|
|notify\_settings|[PeerNotifySettings](../types/PeerNotifySettings.md) | Required|



### Type: [ChatFull](../types/ChatFull.md)


### Example:

```
$chatFull = ['_' => 'chatFull', 'id' => int, 'participants' => ChatParticipants, 'chat_photo' => Photo, 'notify_settings' => PeerNotifySettings, ];
```  

