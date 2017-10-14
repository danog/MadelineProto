---
title: contacts.blocked
description: contacts_blocked attributes, type and example
---
## Constructor: contacts.blocked  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|---------------|----------|
|blocked|Array of [ContactBlocked](../types/ContactBlocked.md) | Yes|
|users|Array of [User](../types/User.md) | Yes|



### Type: [contacts\_Blocked](../types/contacts_Blocked.md)


### Example:

```
$contacts_blocked = ['_' => 'contacts.blocked', 'blocked' => [ContactBlocked], 'users' => [User]];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "contacts.blocked", "blocked": [ContactBlocked], "users": [User]}
```


Or, if you're into Lua:  


```
contacts_blocked={_='contacts.blocked', blocked={ContactBlocked}, users={User}}

```


