---
title: messageService
description: messageService attributes, type and example
---
## Constructor: messageService  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|---------------|----------|
|out|[Bool](../types/Bool.md) | Optional|
|mentioned|[Bool](../types/Bool.md) | Optional|
|media\_unread|[Bool](../types/Bool.md) | Optional|
|silent|[Bool](../types/Bool.md) | Optional|
|post|[Bool](../types/Bool.md) | Optional|
|id|[int](../types/int.md) | Yes|
|from\_id|[int](../types/int.md) | Optional|
|to\_id|[Peer](../types/Peer.md) | Yes|
|reply\_to\_msg\_id|[int](../types/int.md) | Optional|
|date|[int](../types/int.md) | Yes|
|action|[MessageAction](../types/MessageAction.md) | Yes|



### Type: [Message](../types/Message.md)


### Example:

```
$messageService = ['_' => 'messageService', 'out' => Bool, 'mentioned' => Bool, 'media_unread' => Bool, 'silent' => Bool, 'post' => Bool, 'id' => int, 'from_id' => int, 'to_id' => Peer, 'reply_to_msg_id' => int, 'date' => int, 'action' => MessageAction];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "messageService", "out": Bool, "mentioned": Bool, "media_unread": Bool, "silent": Bool, "post": Bool, "id": int, "from_id": int, "to_id": Peer, "reply_to_msg_id": int, "date": int, "action": MessageAction}
```


Or, if you're into Lua:  


```
messageService={_='messageService', out=Bool, mentioned=Bool, media_unread=Bool, silent=Bool, post=Bool, id=int, from_id=int, to_id=Peer, reply_to_msg_id=int, date=int, action=MessageAction}

```


