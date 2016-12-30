---
title: contacts_topPeers
description: contacts_topPeers attributes, type and example
---
## Constructor: contacts\_topPeers  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|:-------------:|---------:|
|categories|Array of [TopPeerCategoryPeers](../types/TopPeerCategoryPeers.md) | Required|
|chats|Array of [Chat](../types/Chat.md) | Required|
|users|Array of [User](../types/User.md) | Required|



### Type: [contacts\_TopPeers](../types/contacts_TopPeers.md)


### Example:

```
$contacts_topPeers = ['_' => contacts_topPeers, 'categories' => [Vector t], 'chats' => [Vector t], 'users' => [Vector t], ];
```