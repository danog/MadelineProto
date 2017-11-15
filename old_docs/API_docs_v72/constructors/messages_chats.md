---
title: messages.chats
description: messages_chats attributes, type and example
---
## Constructor: messages.chats  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|---------------|----------|
|chats|Array of [Chat](../types/Chat.md) | Yes|



### Type: [messages\_Chats](../types/messages_Chats.md)


### Example:

```
$messages_chats = ['_' => 'messages.chats', 'chats' => [Chat]];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "messages.chats", "chats": [Chat]}
```


Or, if you're into Lua:  


```
messages_chats={_='messages.chats', chats={Chat}}

```


