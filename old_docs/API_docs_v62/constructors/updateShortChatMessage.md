---
title: updateShortChatMessage
description: updateShortChatMessage attributes, type and example
---
## Constructor: updateShortChatMessage  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|---------------|----------|
|out|[Bool](../types/Bool.md) | Optional|
|mentioned|[Bool](../types/Bool.md) | Optional|
|media\_unread|[Bool](../types/Bool.md) | Optional|
|silent|[Bool](../types/Bool.md) | Optional|
|id|[int](../types/int.md) | Yes|
|from\_id|[int](../types/int.md) | Yes|
|chat\_id|[int](../types/int.md) | Yes|
|message|[string](../types/string.md) | Yes|
|pts|[int](../types/int.md) | Yes|
|pts\_count|[int](../types/int.md) | Yes|
|date|[int](../types/int.md) | Yes|
|fwd\_from|[MessageFwdHeader](../types/MessageFwdHeader.md) | Optional|
|via\_bot\_id|[int](../types/int.md) | Optional|
|reply\_to\_msg\_id|[int](../types/int.md) | Optional|
|entities|Array of [MessageEntity](../types/MessageEntity.md) | Optional|



### Type: [Updates](../types/Updates.md)


### Example:

```
$updateShortChatMessage = ['_' => 'updateShortChatMessage', 'out' => Bool, 'mentioned' => Bool, 'media_unread' => Bool, 'silent' => Bool, 'id' => int, 'from_id' => int, 'chat_id' => int, 'message' => 'string', 'pts' => int, 'pts_count' => int, 'date' => int, 'fwd_from' => MessageFwdHeader, 'via_bot_id' => int, 'reply_to_msg_id' => int, 'entities' => [MessageEntity]];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "updateShortChatMessage", "out": Bool, "mentioned": Bool, "media_unread": Bool, "silent": Bool, "id": int, "from_id": int, "chat_id": int, "message": "string", "pts": int, "pts_count": int, "date": int, "fwd_from": MessageFwdHeader, "via_bot_id": int, "reply_to_msg_id": int, "entities": [MessageEntity]}
```


Or, if you're into Lua:  


```
updateShortChatMessage={_='updateShortChatMessage', out=Bool, mentioned=Bool, media_unread=Bool, silent=Bool, id=int, from_id=int, chat_id=int, message='string', pts=int, pts_count=int, date=int, fwd_from=MessageFwdHeader, via_bot_id=int, reply_to_msg_id=int, entities={MessageEntity}}

```


