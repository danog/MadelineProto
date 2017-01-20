---
title: contacts.importedContacts
description: contacts_importedContacts attributes, type and example
---
## Constructor: contacts.importedContacts  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|:-------------:|---------:|
|imported|Array of [ImportedContact](../types/ImportedContact.md) | Required|
|retry\_contacts|Array of [long](../types/long.md) | Required|
|users|Array of [User](../types/User.md) | Required|



### Type: [contacts\_ImportedContacts](../types/contacts_ImportedContacts.md)


### Example:

```
$contacts_importedContacts = ['_' => 'contacts.importedContacts', 'imported' => [Vector t], 'retry_contacts' => [Vector t], 'users' => [Vector t], ];
```  

