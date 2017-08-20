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
|pts|[int](../types/int.md) | Yes|
|seq|[int](../types/int.md) | Yes|



### Type: [messages\_SentMessage](../types/messages_SentMessage.md)


### Example:

```
$messages_sentMessage = ['_' => 'messages.sentMessage', 'id' => int, 'date' => int, 'pts' => int, 'seq' => int];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "messages.sentMessage", "id": int, "date": int, "pts": int, "seq": int}
```


Or, if you're into Lua:  


```
messages_sentMessage={_='messages.sentMessage', id=int, date=int, pts=int, seq=int}

```


