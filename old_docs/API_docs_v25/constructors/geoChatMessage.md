---
title: geoChatMessage
description: geoChatMessage attributes, type and example
---
## Constructor: geoChatMessage  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|---------------|----------|
|chat\_id|[int](../types/int.md) | Yes|
|id|[int](../types/int.md) | Yes|
|from\_id|[int](../types/int.md) | Yes|
|date|[int](../types/int.md) | Yes|
|message|[string](../types/string.md) | Yes|
|media|[MessageMedia](../types/MessageMedia.md) | Yes|



### Type: [GeoChatMessage](../types/GeoChatMessage.md)


### Example:

```
$geoChatMessage = ['_' => 'geoChatMessage', 'chat_id' => int, 'id' => int, 'from_id' => int, 'date' => int, 'message' => 'string', 'media' => MessageMedia];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "geoChatMessage", "chat_id": int, "id": int, "from_id": int, "date": int, "message": "string", "media": MessageMedia}
```


Or, if you're into Lua:  


```
geoChatMessage={_='geoChatMessage', chat_id=int, id=int, from_id=int, date=int, message='string', media=MessageMedia}

```


