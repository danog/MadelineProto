---
title: userProfilePhoto
description: userProfilePhoto attributes, type and example
---
## Constructor: userProfilePhoto  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|:-------------:|---------:|
|photo\_id|[long](../types/long.md) | Required|
|photo\_small|[FileLocation](../types/FileLocation.md) | Required|
|photo\_big|[FileLocation](../types/FileLocation.md) | Required|



### Type: [UserProfilePhoto](../types/UserProfilePhoto.md)


### Example:

```
$userProfilePhoto = ['_' => userProfilePhoto', 'photo_id' => long, 'photo_small' => FileLocation, 'photo_big' => FileLocation, ];
```