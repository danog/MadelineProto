---
title: messages.statedMessageLink
description: messages_statedMessageLink attributes, type and example
---
## Constructor: messages.statedMessageLink  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|---------------|----------|
|message|[Message](../types/Message.md) | Yes|
|chats|Array of [Chat](../types/Chat.md) | Yes|
|users|Array of [User](../types/User.md) | Yes|
|pts|[int](../types/int.md) | Yes|
|pts\_count|[int](../types/int.md) | Yes|
|links|Array of [contacts\_Link](../types/contacts_Link.md) | Yes|
|seq|[int](../types/int.md) | Yes|



### Type: [messages\_StatedMessage](../types/messages_StatedMessage.md)


### Example:

```
$messages_statedMessageLink = ['_' => 'messages.statedMessageLink', 'message' => Message, 'chats' => [Chat], 'users' => [User], 'pts' => int, 'pts_count' => int, 'links' => [contacts_Link], 'seq' => int];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "messages.statedMessageLink", "message": Message, "chats": [Chat], "users": [User], "pts": int, "pts_count": int, "links": [contacts_Link], "seq": int}
```


Or, if you're into Lua:  


```
messages_statedMessageLink={_='messages.statedMessageLink', message=Message, chats={Chat}, users={User}, pts=int, pts_count=int, links={contacts_Link}, seq=int}

```


