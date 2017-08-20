---
title: messages.dialogsSlice
description: messages_dialogsSlice attributes, type and example
---
## Constructor: messages.dialogsSlice  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|---------------|----------|
|count|[int](../types/int.md) | Yes|
|dialogs|Array of [Dialog](../types/Dialog.md) | Yes|
|messages|Array of [Message](../types/Message.md) | Yes|
|chats|Array of [Chat](../types/Chat.md) | Yes|
|users|Array of [User](../types/User.md) | Yes|



### Type: [messages\_Dialogs](../types/messages_Dialogs.md)


### Example:

```
$messages_dialogsSlice = ['_' => 'messages.dialogsSlice', 'count' => int, 'dialogs' => [Dialog], 'messages' => [Message], 'chats' => [Chat], 'users' => [User]];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "messages.dialogsSlice", "count": int, "dialogs": [Dialog], "messages": [Message], "chats": [Chat], "users": [User]}
```


Or, if you're into Lua:  


```
messages_dialogsSlice={_='messages.dialogsSlice', count=int, dialogs={Dialog}, messages={Message}, chats={Chat}, users={User}}

```


