---
title: updateChatParticipantAdd
description: updateChatParticipantAdd attributes, type and example
---
## Constructor: updateChatParticipantAdd  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|---------------|----------|
|chat\_id|[int](../types/int.md) | Yes|
|user\_id|[int](../types/int.md) | Yes|
|inviter\_id|[int](../types/int.md) | Yes|
|version|[int](../types/int.md) | Yes|



### Type: [Update](../types/Update.md)


### Example:

```
$updateChatParticipantAdd = ['_' => 'updateChatParticipantAdd', 'chat_id' => int, 'user_id' => int, 'inviter_id' => int, 'version' => int];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "updateChatParticipantAdd", "chat_id": int, "user_id": int, "inviter_id": int, "version": int}
```


Or, if you're into Lua:  


```
updateChatParticipantAdd={_='updateChatParticipantAdd', chat_id=int, user_id=int, inviter_id=int, version=int}

```


