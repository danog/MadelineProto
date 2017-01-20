---
title: messages.sentMessageLink
description: messages_sentMessageLink attributes, type and example
---
## Constructor: messages.sentMessageLink  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|:-------------:|---------:|
|id|[int](../types/int.md) | Required|
|date|[int](../types/int.md) | Required|
|media|[MessageMedia](../types/MessageMedia.md) | Required|
|pts|[int](../types/int.md) | Required|
|pts\_count|[int](../types/int.md) | Required|
|links|Array of [contacts\_Link](../types/contacts_Link.md) | Required|
|seq|[int](../types/int.md) | Required|



### Type: [messages\_SentMessage](../types/messages_SentMessage.md)


### Example:

```
$messages_sentMessageLink = ['_' => 'messages.sentMessageLink', 'id' => int, 'date' => int, 'media' => MessageMedia, 'pts' => int, 'pts_count' => int, 'links' => [Vector t], 'seq' => int, ];
```  

