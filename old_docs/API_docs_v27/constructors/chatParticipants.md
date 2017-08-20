---
title: chatParticipants
description: chatParticipants attributes, type and example
---
## Constructor: chatParticipants  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|---------------|----------|
|chat\_id|[int](../types/int.md) | Yes|
|admin\_id|[int](../types/int.md) | Yes|
|participants|Array of [ChatParticipant](../types/ChatParticipant.md) | Yes|
|version|[int](../types/int.md) | Yes|



### Type: [ChatParticipants](../types/ChatParticipants.md)


### Example:

```
$chatParticipants = ['_' => 'chatParticipants', 'chat_id' => int, 'admin_id' => int, 'participants' => [ChatParticipant], 'version' => int];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "chatParticipants", "chat_id": int, "admin_id": int, "participants": [ChatParticipant], "version": int}
```


Or, if you're into Lua:  


```
chatParticipants={_='chatParticipants', chat_id=int, admin_id=int, participants={ChatParticipant}, version=int}

```


