---
title: updateUserPhoto
description: updateUserPhoto attributes, type and example
---
## Constructor: updateUserPhoto  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|---------------|----------|
|user\_id|[int](../types/int.md) | Yes|
|date|[int](../types/int.md) | Yes|
|photo|[UserProfilePhoto](../types/UserProfilePhoto.md) | Yes|
|previous|[Bool](../types/Bool.md) | Yes|



### Type: [Update](../types/Update.md)


### Example:

```
$updateUserPhoto = ['_' => 'updateUserPhoto', 'user_id' => int, 'date' => int, 'photo' => UserProfilePhoto, 'previous' => Bool];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "updateUserPhoto", "user_id": int, "date": int, "photo": UserProfilePhoto, "previous": Bool}
```


Or, if you're into Lua:  


```
updateUserPhoto={_='updateUserPhoto', user_id=int, date=int, photo=UserProfilePhoto, previous=Bool}

```


