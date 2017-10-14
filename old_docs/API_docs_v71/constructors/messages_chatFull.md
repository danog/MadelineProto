---
title: messages.chatFull
description: messages_chatFull attributes, type and example
---
## Constructor: messages.chatFull  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|---------------|----------|
|full\_chat|[ChatFull](../types/ChatFull.md) | Yes|
|chats|Array of [Chat](../types/Chat.md) | Yes|
|users|Array of [User](../types/User.md) | Yes|



### Type: [messages\_ChatFull](../types/messages_ChatFull.md)


### Example:

```
$messages_chatFull = ['_' => 'messages.chatFull', 'full_chat' => ChatFull, 'chats' => [Chat], 'users' => [User]];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "messages.chatFull", "full_chat": ChatFull, "chats": [Chat], "users": [User]}
```


Or, if you're into Lua:  


```
messages_chatFull={_='messages.chatFull', full_chat=ChatFull, chats={Chat}, users={User}}

```


