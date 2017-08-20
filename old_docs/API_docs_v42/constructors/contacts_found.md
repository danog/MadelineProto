---
title: contacts.found
description: contacts_found attributes, type and example
---
## Constructor: contacts.found  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|---------------|----------|
|results|Array of [Peer](../types/Peer.md) | Yes|
|chats|Array of [Chat](../types/Chat.md) | Yes|
|users|Array of [User](../types/User.md) | Yes|



### Type: [contacts\_Found](../types/contacts_Found.md)


### Example:

```
$contacts_found = ['_' => 'contacts.found', 'results' => [Peer], 'chats' => [Chat], 'users' => [User]];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "contacts.found", "results": [Peer], "chats": [Chat], "users": [User]}
```


Or, if you're into Lua:  


```
contacts_found={_='contacts.found', results={Peer}, chats={Chat}, users={User}}

```


