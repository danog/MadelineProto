---
title: messages.statedMessagesLinks
description: messages_statedMessagesLinks attributes, type and example
---
## Constructor: messages.statedMessagesLinks  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|---------------|----------|
|messages|Array of [Message](../types/Message.md) | Yes|
|chats|Array of [Chat](../types/Chat.md) | Yes|
|users|Array of [User](../types/User.md) | Yes|
|links|Array of [contacts\_Link](../types/contacts_Link.md) | Yes|
|pts|[int](../types/int.md) | Yes|
|seq|[int](../types/int.md) | Yes|



### Type: [messages\_StatedMessages](../types/messages_StatedMessages.md)


### Example:

```
$messages_statedMessagesLinks = ['_' => 'messages.statedMessagesLinks', 'messages' => [Message], 'chats' => [Chat], 'users' => [User], 'links' => [contacts_Link], 'pts' => int, 'seq' => int];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "messages.statedMessagesLinks", "messages": [Message], "chats": [Chat], "users": [User], "links": [contacts_Link], "pts": int, "seq": int}
```


Or, if you're into Lua:  


```
messages_statedMessagesLinks={_='messages.statedMessagesLinks', messages={Message}, chats={Chat}, users={User}, links={contacts_Link}, pts=int, seq=int}

```


