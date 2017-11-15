---
title: contacts.blockedSlice
description: contacts_blockedSlice attributes, type and example
---
## Constructor: contacts.blockedSlice  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|---------------|----------|
|count|[int](../types/int.md) | Yes|
|blocked|Array of [ContactBlocked](../types/ContactBlocked.md) | Yes|
|users|Array of [User](../types/User.md) | Yes|



### Type: [contacts\_Blocked](../types/contacts_Blocked.md)


### Example:

```
$contacts_blockedSlice = ['_' => 'contacts.blockedSlice', 'count' => int, 'blocked' => [ContactBlocked], 'users' => [User]];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "contacts.blockedSlice", "count": int, "blocked": [ContactBlocked], "users": [User]}
```


Or, if you're into Lua:  


```
contacts_blockedSlice={_='contacts.blockedSlice', count=int, blocked={ContactBlocked}, users={User}}

```


