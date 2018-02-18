---
title: updateShortMessage
description: updateShortMessage attributes, type and example
---
## Constructor: updateShortMessage  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|---------------|----------|
|out|[Bool](../types/Bool.md) | Optional|
|mentioned|[Bool](../types/Bool.md) | Optional|
|media\_unread|[Bool](../types/Bool.md) | Optional|
|silent|[Bool](../types/Bool.md) | Optional|
|id|[int](../types/int.md) | Yes|
|user\_id|[int](../types/int.md) | Yes|
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
$updateShortMessage = ['_' => 'updateShortMessage', 'out' => Bool, 'mentioned' => Bool, 'media_unread' => Bool, 'silent' => Bool, 'id' => int, 'user_id' => int, 'message' => 'string', 'pts' => int, 'pts_count' => int, 'date' => int, 'fwd_from' => MessageFwdHeader, 'via_bot_id' => int, 'reply_to_msg_id' => int, 'entities' => [MessageEntity]];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "updateShortMessage", "out": Bool, "mentioned": Bool, "media_unread": Bool, "silent": Bool, "id": int, "user_id": int, "message": "string", "pts": int, "pts_count": int, "date": int, "fwd_from": MessageFwdHeader, "via_bot_id": int, "reply_to_msg_id": int, "entities": [MessageEntity]}
```


Or, if you're into Lua:  


```
updateShortMessage={_='updateShortMessage', out=Bool, mentioned=Bool, media_unread=Bool, silent=Bool, id=int, user_id=int, message='string', pts=int, pts_count=int, date=int, fwd_from=MessageFwdHeader, via_bot_id=int, reply_to_msg_id=int, entities={MessageEntity}}

```


