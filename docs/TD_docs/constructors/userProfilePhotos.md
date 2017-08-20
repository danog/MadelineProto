---
title: userProfilePhotos
description: Contains part of the list of user photos
---
## Constructor: userProfilePhotos  
[Back to constructors index](index.md)



Contains part of the list of user photos

### Attributes:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|total\_count|[int](../types/int.md) | Yes|Total number of user profile photos|
|photos|Array of [photo](../constructors/photo.md) | Yes|List of photos|



### Type: [UserProfilePhotos](../types/UserProfilePhotos.md)


### Example:

```
$userProfilePhotos = ['_' => 'userProfilePhotos', 'total_count' => int, 'photos' => [photo]];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "userProfilePhotos", "total_count": int, "photos": [photo]}
```


Or, if you're into Lua:  


```
userProfilePhotos={_='userProfilePhotos', total_count=int, photos={photo}}

```


