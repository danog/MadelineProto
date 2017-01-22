---
title: auth.authorization
description: auth_authorization attributes, type and example
---
## Constructor: auth.authorization  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|:-------------:|---------:|
|tmp\_sessions|[int](../types/int.md) | Optional|
|user|[User](../types/User.md) | Required|



### Type: [auth\_Authorization](../types/auth_Authorization.md)


### Example:

```
$auth_authorization = ['_' => 'auth.authorization', 'tmp_sessions' => int, 'user' => User, ];
```  

