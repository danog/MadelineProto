---
title: message
description: message attributes, type and example
---
## Constructor: message  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|---------------|----------|
|id|[int](../types/int.md) | Yes|
|from\_id|[int](../types/int.md) | Yes|
|to\_id|[Peer](../types/Peer.md) | Yes|
|date|[int](../types/int.md) | Yes|
|message|[string](../types/string.md) | Yes|
|media|[MessageMedia](../types/MessageMedia.md) | Yes|



### Type: [Message](../types/Message.md)


### Example:

```
$message = ['_' => 'message', 'id' => int, 'from_id' => int, 'to_id' => Peer, 'date' => int, 'message' => 'string', 'media' => MessageMedia];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "message", "id": int, "from_id": int, "to_id": Peer, "date": int, "message": "string", "media": MessageMedia}
```


Or, if you're into Lua:  


```
message={_='message', id=int, from_id=int, to_id=Peer, date=int, message='string', media=MessageMedia}

```


