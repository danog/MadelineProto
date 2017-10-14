---
title: messages.peerDialogs
description: messages_peerDialogs attributes, type and example
---
## Constructor: messages.peerDialogs  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|---------------|----------|
|dialogs|Array of [Dialog](../types/Dialog.md) | Yes|
|messages|Array of [Message](../types/Message.md) | Yes|
|chats|Array of [Chat](../types/Chat.md) | Yes|
|users|Array of [User](../types/User.md) | Yes|
|state|[updates\_State](../types/updates_State.md) | Yes|



### Type: [messages\_PeerDialogs](../types/messages_PeerDialogs.md)


### Example:

```
$messages_peerDialogs = ['_' => 'messages.peerDialogs', 'dialogs' => [Dialog], 'messages' => [Message], 'chats' => [Chat], 'users' => [User], 'state' => updates_State];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "messages.peerDialogs", "dialogs": [Dialog], "messages": [Message], "chats": [Chat], "users": [User], "state": updates_State}
```


Or, if you're into Lua:  


```
messages_peerDialogs={_='messages.peerDialogs', dialogs={Dialog}, messages={Message}, chats={Chat}, users={User}, state=updates_State}

```


