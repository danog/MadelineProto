---
title: geochats.messages
description: geochats_messages attributes, type and example
---
## Constructor: geochats.messages  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|---------------|----------|
|messages|Array of [GeoChatMessage](../types/GeoChatMessage.md) | Yes|
|chats|Array of [Chat](../types/Chat.md) | Yes|
|users|Array of [User](../types/User.md) | Yes|



### Type: [geochats\_Messages](../types/geochats_Messages.md)


### Example:

```
$geochats_messages = ['_' => 'geochats.messages', 'messages' => [GeoChatMessage], 'chats' => [Chat], 'users' => [User]];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "geochats.messages", "messages": [GeoChatMessage], "chats": [Chat], "users": [User]}
```


Or, if you're into Lua:  


```
geochats_messages={_='geochats.messages', messages={GeoChatMessage}, chats={Chat}, users={User}}

```


