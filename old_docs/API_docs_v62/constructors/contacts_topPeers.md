---
title: contacts.topPeers
description: contacts_topPeers attributes, type and example
---
## Constructor: contacts.topPeers  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|---------------|----------|
|categories|Array of [TopPeerCategoryPeers](../types/TopPeerCategoryPeers.md) | Yes|
|chats|Array of [Chat](../types/Chat.md) | Yes|
|users|Array of [User](../types/User.md) | Yes|



### Type: [contacts\_TopPeers](../types/contacts_TopPeers.md)


### Example:

```
$contacts_topPeers = ['_' => 'contacts.topPeers', 'categories' => [TopPeerCategoryPeers], 'chats' => [Chat], 'users' => [User]];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "contacts.topPeers", "categories": [TopPeerCategoryPeers], "chats": [Chat], "users": [User]}
```


Or, if you're into Lua:  


```
contacts_topPeers={_='contacts.topPeers', categories={TopPeerCategoryPeers}, chats={Chat}, users={User}}

```


