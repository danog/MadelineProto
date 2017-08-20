---
title: geoChatMessageService
description: geoChatMessageService attributes, type and example
---
## Constructor: geoChatMessageService  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|---------------|----------|
|chat\_id|[int](../types/int.md) | Yes|
|id|[int](../types/int.md) | Yes|
|from\_id|[int](../types/int.md) | Yes|
|date|[int](../types/int.md) | Yes|
|action|[MessageAction](../types/MessageAction.md) | Yes|



### Type: [GeoChatMessage](../types/GeoChatMessage.md)


### Example:

```
$geoChatMessageService = ['_' => 'geoChatMessageService', 'chat_id' => int, 'id' => int, 'from_id' => int, 'date' => int, 'action' => MessageAction];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "geoChatMessageService", "chat_id": int, "id": int, "from_id": int, "date": int, "action": MessageAction}
```


Or, if you're into Lua:  


```
geoChatMessageService={_='geoChatMessageService', chat_id=int, id=int, from_id=int, date=int, action=MessageAction}

```


