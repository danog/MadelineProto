---
title: messageForwarded
description: messageForwarded attributes, type and example
---
## Constructor: messageForwarded  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|---------------|----------|
|id|[int](../types/int.md) | Yes|
|fwd\_from\_id|[int](../types/int.md) | Yes|
|fwd\_date|[int](../types/int.md) | Yes|
|from\_id|[int](../types/int.md) | Yes|
|to\_id|[Peer](../types/Peer.md) | Yes|
|date|[int](../types/int.md) | Yes|
|message|[string](../types/string.md) | Yes|
|media|[MessageMedia](../types/MessageMedia.md) | Yes|



### Type: [Message](../types/Message.md)


### Example:

```
$messageForwarded = ['_' => 'messageForwarded', 'id' => int, 'fwd_from_id' => int, 'fwd_date' => int, 'from_id' => int, 'to_id' => Peer, 'date' => int, 'message' => 'string', 'media' => MessageMedia];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "messageForwarded", "id": int, "fwd_from_id": int, "fwd_date": int, "from_id": int, "to_id": Peer, "date": int, "message": "string", "media": MessageMedia}
```


Or, if you're into Lua:  


```
messageForwarded={_='messageForwarded', id=int, fwd_from_id=int, fwd_date=int, from_id=int, to_id=Peer, date=int, message='string', media=MessageMedia}

```


