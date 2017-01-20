---
title: contacts.blockedSlice
description: contacts_blockedSlice attributes, type and example
---
## Constructor: contacts.blockedSlice  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|:-------------:|---------:|
|count|[int](../types/int.md) | Required|
|blocked|Array of [ContactBlocked](../types/ContactBlocked.md) | Required|
|users|Array of [User](../types/User.md) | Required|



### Type: [contacts\_Blocked](../types/contacts_Blocked.md)


### Example:

```
$contacts_blockedSlice = ['_' => 'contacts.blockedSlice', 'count' => int, 'blocked' => [Vector t], 'users' => [Vector t], ];
```  

