---
title: user
description: user attributes, type and example
---
## Constructor: user  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|:-------------:|---------:|
|id|[int](../types/int.md) | Required|
|access\_hash|[long](../types/long.md) | Optional|
|first\_name|[string](../types/string.md) | Optional|
|last\_name|[string](../types/string.md) | Optional|
|username|[string](../types/string.md) | Optional|
|phone|[string](../types/string.md) | Optional|
|photo|[UserProfilePhoto](../types/UserProfilePhoto.md) | Optional|
|status|[UserStatus](../types/UserStatus.md) | Optional|
|bot\_info\_version|[int](../types/int.md) | Optional|



### Type: [User](../types/User.md)


### Example:

```
$user = ['_' => 'user', 'id' => int, 'access_hash' => long, 'first_name' => string, 'last_name' => string, 'username' => string, 'phone' => string, 'photo' => UserProfilePhoto, 'status' => UserStatus, 'bot_info_version' => int, ];
```  

The following syntaxes can also be used:

```
$user = '@username'; // Username

$user = 44700; // bot API id (users)
$user = -492772765; // bot API id (chats)
$user = -10038575794; // bot API id (channels)

$user = 'user#44700'; // tg-cli style id (users)
$user = 'chat#492772765'; // tg-cli style id (chats)
$user = 'channel#38575794'; // tg-cli style id (channels)
```