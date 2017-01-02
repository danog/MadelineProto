---
title: userForeign
description: userForeign attributes, type and example
---
## Constructor: userForeign  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|:-------------:|---------:|
|id|[int](../types/int.md) | Required|
|first\_name|[string](../types/string.md) | Required|
|last\_name|[string](../types/string.md) | Required|
|username|[string](../types/string.md) | Required|
|access\_hash|[long](../types/long.md) | Required|
|photo|[UserProfilePhoto](../types/UserProfilePhoto.md) | Required|
|status|[UserStatus](../types/UserStatus.md) | Required|



### Type: [User](../types/User.md)


### Example:

```
$userForeign = ['_' => 'userForeign', 'id' => int, 'first_name' => string, 'last_name' => string, 'username' => string, 'access_hash' => long, 'photo' => UserProfilePhoto, 'status' => UserStatus, ];
```  

The following syntaxes can also be used:

```
$userForeign = '@username'; // Username

$userForeign = 44700; // bot API id (users)
$userForeign = -492772765; // bot API id (chats)
$userForeign = -10038575794; // bot API id (channels)

$userForeign = 'user#44700'; // tg-cli style id (users)
$userForeign = 'chat#492772765'; // tg-cli style id (chats)
$userForeign = 'channel#38575794'; // tg-cli style id (channels)
```