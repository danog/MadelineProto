---
title: chatParticipantsForbidden
description: chatParticipantsForbidden attributes, type and example
---
## Constructor: chatParticipantsForbidden  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|---------------|----------|
|chat\_id|[int](../types/int.md) | Yes|
|self\_participant|[ChatParticipant](../types/ChatParticipant.md) | Optional|



### Type: [ChatParticipants](../types/ChatParticipants.md)


### Example:

```
$chatParticipantsForbidden = ['_' => 'chatParticipantsForbidden', 'chat_id' => int, 'self_participant' => ChatParticipant];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "chatParticipantsForbidden", "chat_id": int, "self_participant": ChatParticipant}
```


Or, if you're into Lua:  


```
chatParticipantsForbidden={_='chatParticipantsForbidden', chat_id=int, self_participant=ChatParticipant}

```


