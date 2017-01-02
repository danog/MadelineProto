---
title: contacts_link
description: contacts_link attributes, type and example
---
## Constructor: contacts\_link  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|:-------------:|---------:|
|my\_link|[contacts\_MyLink](../types/contacts_MyLink.md) | Required|
|foreign\_link|[contacts\_ForeignLink](../types/contacts_ForeignLink.md) | Required|
|user|[User](../types/User.md) | Required|



### Type: [contacts\_Link](../types/contacts_Link.md)


### Example:

```
$contacts_link = ['_' => 'contacts_link', 'my_link' => contacts.MyLink, 'foreign_link' => contacts.ForeignLink, 'user' => User, ];
```  

