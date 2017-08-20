---
title: photos.photosSlice
description: photos_photosSlice attributes, type and example
---
## Constructor: photos.photosSlice  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|---------------|----------|
|count|[int](../types/int.md) | Yes|
|photos|Array of [Photo](../types/Photo.md) | Yes|
|users|Array of [User](../types/User.md) | Yes|



### Type: [photos\_Photos](../types/photos_Photos.md)


### Example:

```
$photos_photosSlice = ['_' => 'photos.photosSlice', 'count' => int, 'photos' => [Photo], 'users' => [User]];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "photos.photosSlice", "count": int, "photos": [Photo], "users": [User]}
```


Or, if you're into Lua:  


```
photos_photosSlice={_='photos.photosSlice', count=int, photos={Photo}, users={User}}

```


