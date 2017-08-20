---
title: messages.statedMessages
description: messages_statedMessages attributes, type and example
---
## Constructor: messages.statedMessages  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|---------------|----------|
|messages|Array of [Message](../types/Message.md) | Yes|
|chats|Array of [Chat](../types/Chat.md) | Yes|
|users|Array of [User](../types/User.md) | Yes|
|pts|[int](../types/int.md) | Yes|
|pts\_count|[int](../types/int.md) | Yes|



### Type: [messages\_StatedMessages](../types/messages_StatedMessages.md)


### Example:

```
$messages_statedMessages = ['_' => 'messages.statedMessages', 'messages' => [Message], 'chats' => [Chat], 'users' => [User], 'pts' => int, 'pts_count' => int];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "messages.statedMessages", "messages": [Message], "chats": [Chat], "users": [User], "pts": int, "pts_count": int}
```


Or, if you're into Lua:  


```
messages_statedMessages={_='messages.statedMessages', messages={Message}, chats={Chat}, users={User}, pts=int, pts_count=int}

```


