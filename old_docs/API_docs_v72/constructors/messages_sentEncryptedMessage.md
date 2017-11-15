---
title: messages.sentEncryptedMessage
description: messages_sentEncryptedMessage attributes, type and example
---
## Constructor: messages.sentEncryptedMessage  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|---------------|----------|
|date|[int](../types/int.md) | Yes|



### Type: [messages\_SentEncryptedMessage](../types/messages_SentEncryptedMessage.md)


### Example:

```
$messages_sentEncryptedMessage = ['_' => 'messages.sentEncryptedMessage', 'date' => int];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "messages.sentEncryptedMessage", "date": int}
```


Or, if you're into Lua:  


```
messages_sentEncryptedMessage={_='messages.sentEncryptedMessage', date=int}

```


