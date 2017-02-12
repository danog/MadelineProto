---
title: userSelf
description: userSelf attributes, type and example
---
## Constructor: userSelf  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|:-------------:|---------:|
|id|[int](../types/int.md) | Required|
|first\_name|[string](../types/string.md) | Required|
|last\_name|[string](../types/string.md) | Required|
|username|[string](../types/string.md) | Required|
|phone|[string](../types/string.md) | Required|
|photo|[UserProfilePhoto](../types/UserProfilePhoto.md) | Required|
|status|[UserStatus](../types/UserStatus.md) | Required|



### Type: [User](../types/User.md)


### Example:

```
$userSelf = ['_' => 'userSelf', 'id' => int, 'first_name' => string, 'last_name' => string, 'username' => string, 'phone' => string, 'photo' => UserProfilePhoto, 'status' => UserStatus, ];
```  

