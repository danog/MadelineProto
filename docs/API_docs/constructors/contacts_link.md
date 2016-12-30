---
title: contacts_link
description: contacts_link attributes, type and example
---
## Constructor: contacts\_link  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|:-------------:|---------:|
|my\_link|[ContactLink](../types/ContactLink.md) | Required|
|foreign\_link|[ContactLink](../types/ContactLink.md) | Required|
|user|[User](../types/User.md) | Required|



### Type: [contacts\_Link](../types/contacts_Link.md)


### Example:

```
$contacts_link = ['_' => contacts_link, 'my_link' => ContactLink, 'foreign_link' => ContactLink, 'user' => User, ];
```