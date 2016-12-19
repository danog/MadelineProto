## Constructor: contacts\_importedContacts  

### Attributes:

| Name     |    Type       | Required |
|----------|:-------------:|---------:|
|imported|Array of [ImportedContact](../types/ImportedContact.md) | Required|
|retry\_contacts|Array of [long](../types/long.md) | Required|
|users|Array of [User](../types/User.md) | Required|


### Type: [contacts\_ImportedContacts](../types/contacts\_ImportedContacts.md)

### Example:


```
$contacts_importedContacts = ['imported' => [ImportedContact], 'retry_contacts' => [long], 'users' => [User], ];
```