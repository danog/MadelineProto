---
title: updateShortChatMessage
description: updateShortChatMessage attributes, type and example
---
## Constructor: updateShortChatMessage  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|---------------|----------|
|id|[int](../types/int.md) | Yes|
|from\_id|[int](../types/int.md) | Yes|
|chat\_id|[int](../types/int.md) | Yes|
|message|[string](../types/string.md) | Yes|
|pts|[int](../types/int.md) | Yes|
|pts\_count|[int](../types/int.md) | Yes|
|date|[int](../types/int.md) | Yes|
|fwd\_from\_id|[Peer](../types/Peer.md) | Optional|
|fwd\_date|[int](../types/int.md) | Optional|
|reply\_to\_msg\_id|[int](../types/int.md) | Optional|
|entities|Array of [MessageEntity](../types/MessageEntity.md) | Optional|



### Type: [Updates](../types/Updates.md)


### Example:

```
$updateShortChatMessage = ['_' => 'updateShortChatMessage', 'id' => int, 'from_id' => int, 'chat_id' => int, 'message' => 'string', 'pts' => int, 'pts_count' => int, 'date' => int, 'fwd_from_id' => Peer, 'fwd_date' => int, 'reply_to_msg_id' => int, 'entities' => [MessageEntity]];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "updateShortChatMessage", "id": int, "from_id": int, "chat_id": int, "message": "string", "pts": int, "pts_count": int, "date": int, "fwd_from_id": Peer, "fwd_date": int, "reply_to_msg_id": int, "entities": [MessageEntity]}
```


Or, if you're into Lua:  


```
updateShortChatMessage={_='updateShortChatMessage', id=int, from_id=int, chat_id=int, message='string', pts=int, pts_count=int, date=int, fwd_from_id=Peer, fwd_date=int, reply_to_msg_id=int, entities={MessageEntity}}

```


