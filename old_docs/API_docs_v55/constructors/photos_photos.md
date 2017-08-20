---
title: photos.photos
description: photos_photos attributes, type and example
---
## Constructor: photos.photos  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|---------------|----------|
|photos|Array of [Photo](../types/Photo.md) | Yes|
|users|Array of [User](../types/User.md) | Yes|



### Type: [photos\_Photos](../types/photos_Photos.md)


### Example:

```
$photos_photos = ['_' => 'photos.photos', 'photos' => [Photo], 'users' => [User]];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "photos.photos", "photos": [Photo], "users": [User]}
```


Or, if you're into Lua:  


```
photos_photos={_='photos.photos', photos={Photo}, users={User}}

```


