---
title: contacts.contacts
description: contacts_contacts attributes, type and example
---
## Constructor: contacts.contacts  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|---------------|----------|
|contacts|Array of [Contact](../types/Contact.md) | Yes|
|users|Array of [User](../types/User.md) | Yes|



### Type: [contacts\_Contacts](../types/contacts_Contacts.md)


### Example:

```
$contacts_contacts = ['_' => 'contacts.contacts', 'contacts' => [Contact], 'users' => [User]];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "contacts.contacts", "contacts": [Contact], "users": [User]}
```


Or, if you're into Lua:  


```
contacts_contacts={_='contacts.contacts', contacts={Contact}, users={User}}

```


