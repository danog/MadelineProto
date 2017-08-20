---
title: geochats.messagesSlice
description: geochats_messagesSlice attributes, type and example
---
## Constructor: geochats.messagesSlice  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|---------------|----------|
|count|[int](../types/int.md) | Yes|
|messages|Array of [GeoChatMessage](../types/GeoChatMessage.md) | Yes|
|chats|Array of [Chat](../types/Chat.md) | Yes|
|users|Array of [User](../types/User.md) | Yes|



### Type: [geochats\_Messages](../types/geochats_Messages.md)


### Example:

```
$geochats_messagesSlice = ['_' => 'geochats.messagesSlice', 'count' => int, 'messages' => [GeoChatMessage], 'chats' => [Chat], 'users' => [User]];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "geochats.messagesSlice", "count": int, "messages": [GeoChatMessage], "chats": [Chat], "users": [User]}
```


Or, if you're into Lua:  


```
geochats_messagesSlice={_='geochats.messagesSlice', count=int, messages={GeoChatMessage}, chats={Chat}, users={User}}

```


