---
title: messageMediaContact
description: messageMediaContact attributes, type and example
---
## Constructor: messageMediaContact  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|:-------------:|---------:|
|phone\_number|[string](../types/string.md) | Required|
|first\_name|[string](../types/string.md) | Required|
|last\_name|[string](../types/string.md) | Required|
|user\_id|[int](../types/int.md) | Required|



### Type: [MessageMedia](../types/MessageMedia.md)


### Example:

```
$messageMediaContact = ['_' => messageMediaContact, 'phone_number' => string, 'first_name' => string, 'last_name' => string, 'user_id' => int, ];
```