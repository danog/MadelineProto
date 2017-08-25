---
title: importContacts
description: Adds new contacts/edits existing contacts, contacts user identifiers are ignored. Returns list of corresponding users in the same order as input contacts. If contact doesn't registered in Telegram, user with id == 0 will be returned
---
## Method: importContacts  
[Back to methods index](index.md)


YOU CANNOT USE THIS METHOD IN MADELINEPROTO


Adds new contacts/edits existing contacts, contacts user identifiers are ignored. Returns list of corresponding users in the same order as input contacts. If contact doesn't registered in Telegram, user with id == 0 will be returned

### Params:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|contacts|Array of [contact](../types/contact.md) | Yes|List of contacts to import/edit|


### Return type: [Users](../types/Users.md)

