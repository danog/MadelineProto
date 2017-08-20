---
title: messageService
description: messageService attributes, type and example
---
## Constructor: messageService  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|---------------|----------|
|id|[int](../types/int.md) | Yes|
|from\_id|[int](../types/int.md) | Optional|
|to\_id|[Peer](../types/Peer.md) | Yes|
|date|[int](../types/int.md) | Yes|
|action|[MessageAction](../types/MessageAction.md) | Yes|



### Type: [Message](../types/Message.md)


### Example:

```
$messageService = ['_' => 'messageService', 'id' => int, 'from_id' => int, 'to_id' => Peer, 'date' => int, 'action' => MessageAction];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "messageService", "id": int, "from_id": int, "to_id": Peer, "date": int, "action": MessageAction}
```


Or, if you're into Lua:  


```
messageService={_='messageService', id=int, from_id=int, to_id=Peer, date=int, action=MessageAction}

```


