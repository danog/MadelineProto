---
title: messages.statedMessage
description: messages_statedMessage attributes, type and example
---
## Constructor: messages.statedMessage  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|---------------|----------|
|message|[Message](../types/Message.md) | Yes|
|chats|Array of [Chat](../types/Chat.md) | Yes|
|users|Array of [User](../types/User.md) | Yes|
|pts|[int](../types/int.md) | Yes|
|pts\_count|[int](../types/int.md) | Yes|



### Type: [messages\_StatedMessage](../types/messages_StatedMessage.md)


### Example:

```
$messages_statedMessage = ['_' => 'messages.statedMessage', 'message' => Message, 'chats' => [Chat], 'users' => [User], 'pts' => int, 'pts_count' => int];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "messages.statedMessage", "message": Message, "chats": [Chat], "users": [User], "pts": int, "pts_count": int}
```


Or, if you're into Lua:  


```
messages_statedMessage={_='messages.statedMessage', message=Message, chats={Chat}, users={User}, pts=int, pts_count=int}

```


