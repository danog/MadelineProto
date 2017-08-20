---
title: chatParticipantAdmin
description: chatParticipantAdmin attributes, type and example
---
## Constructor: chatParticipantAdmin  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|---------------|----------|
|user\_id|[int](../types/int.md) | Yes|
|inviter\_id|[int](../types/int.md) | Yes|
|date|[int](../types/int.md) | Yes|



### Type: [ChatParticipant](../types/ChatParticipant.md)


### Example:

```
$chatParticipantAdmin = ['_' => 'chatParticipantAdmin', 'user_id' => int, 'inviter_id' => int, 'date' => int];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "chatParticipantAdmin", "user_id": int, "inviter_id": int, "date": int}
```


Or, if you're into Lua:  


```
chatParticipantAdmin={_='chatParticipantAdmin', user_id=int, inviter_id=int, date=int}

```


