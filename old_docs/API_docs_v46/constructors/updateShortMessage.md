---
title: updateShortMessage
description: updateShortMessage attributes, type and example
---
## Constructor: updateShortMessage  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|---------------|----------|
|unread|[Bool](../types/Bool.md) | Optional|
|out|[Bool](../types/Bool.md) | Optional|
|mentioned|[Bool](../types/Bool.md) | Optional|
|media\_unread|[Bool](../types/Bool.md) | Optional|
|id|[int](../types/int.md) | Yes|
|user\_id|[int](../types/int.md) | Yes|
|message|[string](../types/string.md) | Yes|
|pts|[int](../types/int.md) | Yes|
|pts\_count|[int](../types/int.md) | Yes|
|date|[int](../types/int.md) | Yes|
|fwd\_from\_id|[Peer](../types/Peer.md) | Optional|
|fwd\_date|[int](../types/int.md) | Optional|
|via\_bot\_id|[int](../types/int.md) | Optional|
|reply\_to\_msg\_id|[int](../types/int.md) | Optional|
|entities|Array of [MessageEntity](../types/MessageEntity.md) | Optional|



### Type: [Updates](../types/Updates.md)


### Example:

```
$updateShortMessage = ['_' => 'updateShortMessage', 'unread' => Bool, 'out' => Bool, 'mentioned' => Bool, 'media_unread' => Bool, 'id' => int, 'user_id' => int, 'message' => 'string', 'pts' => int, 'pts_count' => int, 'date' => int, 'fwd_from_id' => Peer, 'fwd_date' => int, 'via_bot_id' => int, 'reply_to_msg_id' => int, 'entities' => [MessageEntity]];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "updateShortMessage", "unread": Bool, "out": Bool, "mentioned": Bool, "media_unread": Bool, "id": int, "user_id": int, "message": "string", "pts": int, "pts_count": int, "date": int, "fwd_from_id": Peer, "fwd_date": int, "via_bot_id": int, "reply_to_msg_id": int, "entities": [MessageEntity]}
```


Or, if you're into Lua:  


```
updateShortMessage={_='updateShortMessage', unread=Bool, out=Bool, mentioned=Bool, media_unread=Bool, id=int, user_id=int, message='string', pts=int, pts_count=int, date=int, fwd_from_id=Peer, fwd_date=int, via_bot_id=int, reply_to_msg_id=int, entities={MessageEntity}}

```


