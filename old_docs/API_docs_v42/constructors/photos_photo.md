---
title: photos.photo
description: photos_photo attributes, type and example
---
## Constructor: photos.photo  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|---------------|----------|
|photo|[Photo](../types/Photo.md) | Yes|
|users|Array of [User](../types/User.md) | Yes|



### Type: [photos\_Photo](../types/photos_Photo.md)


### Example:

```
$photos_photo = ['_' => 'photos.photo', 'photo' => Photo, 'users' => [User]];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "photos.photo", "photo": Photo, "users": [User]}
```


Or, if you're into Lua:  


```
photos_photo={_='photos.photo', photo=Photo, users={User}}

```


