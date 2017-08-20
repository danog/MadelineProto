---
title: userSelf
description: userSelf attributes, type and example
---
## Constructor: userSelf  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|---------------|----------|
|id|[int](../types/int.md) | Yes|
|first\_name|[string](../types/string.md) | Yes|
|last\_name|[string](../types/string.md) | Yes|
|username|[string](../types/string.md) | Yes|
|phone|[string](../types/string.md) | Yes|
|photo|[UserProfilePhoto](../types/UserProfilePhoto.md) | Yes|
|status|[UserStatus](../types/UserStatus.md) | Yes|
|inactive|[Bool](../types/Bool.md) | Yes|



### Type: [User](../types/User.md)


### Example:

```
$userSelf = ['_' => 'userSelf', 'id' => int, 'first_name' => 'string', 'last_name' => 'string', 'username' => 'string', 'phone' => 'string', 'photo' => UserProfilePhoto, 'status' => UserStatus, 'inactive' => Bool];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "userSelf", "id": int, "first_name": "string", "last_name": "string", "username": "string", "phone": "string", "photo": UserProfilePhoto, "status": UserStatus, "inactive": Bool}
```


Or, if you're into Lua:  


```
userSelf={_='userSelf', id=int, first_name='string', last_name='string', username='string', phone='string', photo=UserProfilePhoto, status=UserStatus, inactive=Bool}

```


