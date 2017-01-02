---
title: userFull
description: userFull attributes, type and example
---
## Constructor: userFull  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|:-------------:|---------:|
|user|[User](../types/User.md) | Required|
|link|[contacts\_Link](../types/contacts_Link.md) | Required|
|profile\_photo|[Photo](../types/Photo.md) | Required|
|notify\_settings|[PeerNotifySettings](../types/PeerNotifySettings.md) | Required|
|blocked|[Bool](../types/Bool.md) | Required|
|real\_first\_name|[string](../types/string.md) | Required|
|real\_last\_name|[string](../types/string.md) | Required|



### Type: [UserFull](../types/UserFull.md)


### Example:

```
$userFull = ['_' => 'userFull', 'user' => User, 'link' => contacts.Link, 'profile_photo' => Photo, 'notify_settings' => PeerNotifySettings, 'blocked' => Bool, 'real_first_name' => string, 'real_last_name' => string, ];
```  

