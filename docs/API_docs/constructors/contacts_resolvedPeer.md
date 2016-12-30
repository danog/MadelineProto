---
title: contacts_resolvedPeer
description: contacts_resolvedPeer attributes, type and example
---
## Constructor: contacts\_resolvedPeer  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|:-------------:|---------:|
|peer|[Peer](../types/Peer.md) | Required|
|chats|Array of [Chat](../types/Chat.md) | Required|
|users|Array of [User](../types/User.md) | Required|



### Type: [contacts\_ResolvedPeer](../types/contacts_ResolvedPeer.md)


### Example:

```
$contacts_resolvedPeer = ['_' => contacts_resolvedPeer, 'peer' => Peer, 'chats' => [Vector t], 'users' => [Vector t], ];
```