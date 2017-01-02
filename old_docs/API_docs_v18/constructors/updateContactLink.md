---
title: updateContactLink
description: updateContactLink attributes, type and example
---
## Constructor: updateContactLink  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|:-------------:|---------:|
|user\_id|[int](../types/int.md) | Required|
|my\_link|[contacts\_MyLink](../types/contacts_MyLink.md) | Required|
|foreign\_link|[contacts\_ForeignLink](../types/contacts_ForeignLink.md) | Required|



### Type: [Update](../types/Update.md)


### Example:

```
$updateContactLink = ['_' => 'updateContactLink', 'user_id' => int, 'my_link' => contacts.MyLink, 'foreign_link' => contacts.ForeignLink, ];
```  

