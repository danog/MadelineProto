---
title: contacts.found
description: contacts_found attributes, type and example
---
## Constructor: contacts.found  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|---------------|----------|
|results|Array of [ContactFound](../types/ContactFound.md) | Yes|
|users|Array of [User](../types/User.md) | Yes|



### Type: [contacts\_Found](../types/contacts_Found.md)


### Example:

```
$contacts_found = ['_' => 'contacts.found', 'results' => [ContactFound], 'users' => [User]];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "contacts.found", "results": [ContactFound], "users": [User]}
```


Or, if you're into Lua:  


```
contacts_found={_='contacts.found', results={ContactFound}, users={User}}

```


