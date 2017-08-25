---
title: getUserProfilePhotos
description: Returns profile photos of the user. Result of this query can't be invalidated, so it must be used with care
---
## Method: getUserProfilePhotos  
[Back to methods index](index.md)


YOU CANNOT USE THIS METHOD IN MADELINEPROTO


Returns profile photos of the user. Result of this query can't be invalidated, so it must be used with care

### Params:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|user\_id|[int](../types/int.md) | Yes|User identifier|
|offset|[int](../types/int.md) | Yes|Photos to skip, must be non-negative|
|limit|[int](../types/int.md) | Yes|Maximum number of photos to be returned, can't be greater than 100|


### Return type: [UserProfilePhotos](../types/UserProfilePhotos.md)

