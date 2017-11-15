---
title: messages.dialogs
description: messages_dialogs attributes, type and example
---
## Constructor: messages.dialogs  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|---------------|----------|
|dialogs|Array of [Dialog](../types/Dialog.md) | Yes|
|messages|Array of [Message](../types/Message.md) | Yes|
|chats|Array of [Chat](../types/Chat.md) | Yes|
|users|Array of [User](../types/User.md) | Yes|



### Type: [messages\_Dialogs](../types/messages_Dialogs.md)


### Example:

```
$messages_dialogs = ['_' => 'messages.dialogs', 'dialogs' => [Dialog], 'messages' => [Message], 'chats' => [Chat], 'users' => [User]];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "messages.dialogs", "dialogs": [Dialog], "messages": [Message], "chats": [Chat], "users": [User]}
```


Or, if you're into Lua:  


```
messages_dialogs={_='messages.dialogs', dialogs={Dialog}, messages={Message}, chats={Chat}, users={User}}

```


