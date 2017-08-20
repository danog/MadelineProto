---
title: contacts.resolvedPeer
description: contacts_resolvedPeer attributes, type and example
---
## Constructor: contacts.resolvedPeer  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|---------------|----------|
|peer|[Peer](../types/Peer.md) | Yes|
|chats|Array of [Chat](../types/Chat.md) | Yes|
|users|Array of [User](../types/User.md) | Yes|



### Type: [contacts\_ResolvedPeer](../types/contacts_ResolvedPeer.md)


### Example:

```
$contacts_resolvedPeer = ['_' => 'contacts.resolvedPeer', 'peer' => Peer, 'chats' => [Chat], 'users' => [User]];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "contacts.resolvedPeer", "peer": Peer, "chats": [Chat], "users": [User]}
```


Or, if you're into Lua:  


```
contacts_resolvedPeer={_='contacts.resolvedPeer', peer=Peer, chats={Chat}, users={User}}

```


