---
title: userContact
description: userContact attributes, type and example
---
## Constructor: userContact  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|---------------|----------|
|id|[int](../types/int.md) | Yes|
|first\_name|[string](../types/string.md) | Yes|
|last\_name|[string](../types/string.md) | Yes|
|username|[string](../types/string.md) | Yes|
|access\_hash|[long](../types/long.md) | Yes|
|phone|[string](../types/string.md) | Yes|
|photo|[UserProfilePhoto](../types/UserProfilePhoto.md) | Yes|
|status|[UserStatus](../types/UserStatus.md) | Yes|



### Type: [User](../types/User.md)


### Example:

```
$userContact = ['_' => 'userContact', 'id' => int, 'first_name' => 'string', 'last_name' => 'string', 'username' => 'string', 'access_hash' => long, 'phone' => 'string', 'photo' => UserProfilePhoto, 'status' => UserStatus];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "userContact", "id": int, "first_name": "string", "last_name": "string", "username": "string", "access_hash": long, "phone": "string", "photo": UserProfilePhoto, "status": UserStatus}
```


Or, if you're into Lua:  


```
userContact={_='userContact', id=int, first_name='string', last_name='string', username='string', access_hash=long, phone='string', photo=UserProfilePhoto, status=UserStatus}

```


