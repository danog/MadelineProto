---
title: messages.sentMessageLink
description: messages_sentMessageLink attributes, type and example
---
## Constructor: messages.sentMessageLink  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|---------------|----------|
|id|[int](../types/int.md) | Yes|
|date|[int](../types/int.md) | Yes|
|media|[MessageMedia](../types/MessageMedia.md) | Yes|
|pts|[int](../types/int.md) | Yes|
|pts\_count|[int](../types/int.md) | Yes|
|links|Array of [contacts\_Link](../types/contacts_Link.md) | Yes|
|seq|[int](../types/int.md) | Yes|



### Type: [messages\_SentMessage](../types/messages_SentMessage.md)


### Example:

```
$messages_sentMessageLink = ['_' => 'messages.sentMessageLink', 'id' => int, 'date' => int, 'media' => MessageMedia, 'pts' => int, 'pts_count' => int, 'links' => [contacts_Link], 'seq' => int];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "messages.sentMessageLink", "id": int, "date": int, "media": MessageMedia, "pts": int, "pts_count": int, "links": [contacts_Link], "seq": int}
```


Or, if you're into Lua:  


```
messages_sentMessageLink={_='messages.sentMessageLink', id=int, date=int, media=MessageMedia, pts=int, pts_count=int, links={contacts_Link}, seq=int}

```


