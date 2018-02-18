---
title: updateChatParticipantDelete
description: updateChatParticipantDelete attributes, type and example
---
## Constructor: updateChatParticipantDelete  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|---------------|----------|
|chat\_id|[int](../types/int.md) | Yes|
|user\_id|[int](../types/int.md) | Yes|
|version|[int](../types/int.md) | Yes|



### Type: [Update](../types/Update.md)


### Example:

```
$updateChatParticipantDelete = ['_' => 'updateChatParticipantDelete', 'chat_id' => int, 'user_id' => int, 'version' => int];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "updateChatParticipantDelete", "chat_id": int, "user_id": int, "version": int}
```


Or, if you're into Lua:  


```
updateChatParticipantDelete={_='updateChatParticipantDelete', chat_id=int, user_id=int, version=int}

```


