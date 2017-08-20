---
title: messages.sentMessage
description: messages_sentMessage attributes, type and example
---
## Constructor: messages.sentMessage  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|---------------|----------|
|id|[int](../types/int.md) | Yes|
|date|[int](../types/int.md) | Yes|
|media|[MessageMedia](../types/MessageMedia.md) | Yes|
|pts|[int](../types/int.md) | Yes|
|pts\_count|[int](../types/int.md) | Yes|



### Type: [messages\_SentMessage](../types/messages_SentMessage.md)


### Example:

```
$messages_sentMessage = ['_' => 'messages.sentMessage', 'id' => int, 'date' => int, 'media' => MessageMedia, 'pts' => int, 'pts_count' => int];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "messages.sentMessage", "id": int, "date": int, "media": MessageMedia, "pts": int, "pts_count": int}
```


Or, if you're into Lua:  


```
messages_sentMessage={_='messages.sentMessage', id=int, date=int, media=MessageMedia, pts=int, pts_count=int}

```


