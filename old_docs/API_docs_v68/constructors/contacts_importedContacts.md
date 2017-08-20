---
title: contacts.importedContacts
description: contacts_importedContacts attributes, type and example
---
## Constructor: contacts.importedContacts  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|---------------|----------|
|imported|Array of [ImportedContact](../types/ImportedContact.md) | Yes|
|retry\_contacts|Array of [long](../types/long.md) | Yes|
|users|Array of [User](../types/User.md) | Yes|



### Type: [contacts\_ImportedContacts](../types/contacts_ImportedContacts.md)


### Example:

```
$contacts_importedContacts = ['_' => 'contacts.importedContacts', 'imported' => [ImportedContact], 'retry_contacts' => [long], 'users' => [User]];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "contacts.importedContacts", "imported": [ImportedContact], "retry_contacts": [long], "users": [User]}
```


Or, if you're into Lua:  


```
contacts_importedContacts={_='contacts.importedContacts', imported={ImportedContact}, retry_contacts={long}, users={User}}

```


