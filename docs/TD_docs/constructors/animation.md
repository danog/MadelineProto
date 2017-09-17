---
title: animation
description: Describes animation file. Animation should be encoded in gif or mp4 format
---
## Constructor: animation  
[Back to constructors index](index.md)



Describes animation file. Animation should be encoded in gif or mp4 format

### Attributes:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|duration|[int](../types/int.md) | Yes|Duration of the animation in seconds as defined by sender|
|width|[int](../types/int.md) | Yes|Width of the animation|
|height|[int](../types/int.md) | Yes|Height of the animation|
|file\_name|[string](../types/string.md) | Yes|Original name of a file as defined by sender|
|mime\_type|[string](../types/string.md) | Yes|MIME type of a file, usually "image/gif" or "video/mp4"|
|thumb|[photoSize](../types/photoSize.md) | Yes|Animation thumb, nullable|
|animation|[file](../types/file.md) | Yes|File with the animation|



### Type: [Animation](../types/Animation.md)


