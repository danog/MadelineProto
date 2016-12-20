---
title: contacts_blockedSlice
---
## Constructor: contacts\_blockedSlice  
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
$contacts_blockedSlice = ['_' => contacts_blockedSlice', 'count' => int, 'blocked' => [Vector t], 'users' => [Vector t], ];
```