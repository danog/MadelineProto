---
title: geochats.located
description: geochats_located attributes, type and example
---
## Constructor: geochats.located  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|---------------|----------|
|results|Array of [ChatLocated](../types/ChatLocated.md) | Yes|
|messages|Array of [GeoChatMessage](../types/GeoChatMessage.md) | Yes|
|chats|Array of [Chat](../types/Chat.md) | Yes|
|users|Array of [User](../types/User.md) | Yes|



### Type: [geochats\_Located](../types/geochats_Located.md)


### Example:

```
$geochats_located = ['_' => 'geochats.located', 'results' => [ChatLocated], 'messages' => [GeoChatMessage], 'chats' => [Chat], 'users' => [User]];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "geochats.located", "results": [ChatLocated], "messages": [GeoChatMessage], "chats": [Chat], "users": [User]}
```


Or, if you're into Lua:  


```
geochats_located={_='geochats.located', results={ChatLocated}, messages={GeoChatMessage}, chats={Chat}, users={User}}

```


