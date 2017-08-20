---
title: messages.messagesSlice
description: messages_messagesSlice attributes, type and example
---
## Constructor: messages.messagesSlice  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|---------------|----------|
|count|[int](../types/int.md) | Yes|
|messages|Array of [Message](../types/Message.md) | Yes|
|chats|Array of [Chat](../types/Chat.md) | Yes|
|users|Array of [User](../types/User.md) | Yes|



### Type: [messages\_Messages](../types/messages_Messages.md)


### Example:

```
$messages_messagesSlice = ['_' => 'messages.messagesSlice', 'count' => int, 'messages' => [Message], 'chats' => [Chat], 'users' => [User]];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "messages.messagesSlice", "count": int, "messages": [Message], "chats": [Chat], "users": [User]}
```


Or, if you're into Lua:  


```
messages_messagesSlice={_='messages.messagesSlice', count=int, messages={Message}, chats={Chat}, users={User}}

```


