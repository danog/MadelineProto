---
title: video
description: Describes video file
---
## Constructor: video  
[Back to constructors index](index.md)



Describes video file

### Attributes:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|duration|[int](../types/int.md) | Yes|Duration of the video in seconds as defined by sender|
|width|[int](../types/int.md) | Yes|Video width as defined by sender|
|height|[int](../types/int.md) | Yes|Video height as defined by sender|
|file\_name|[string](../types/string.md) | Yes|Original name of a file as defined by sender|
|mime\_type|[string](../types/string.md) | Yes|MIME type of a file as defined by sender|
|has\_stickers|[Bool](../types/Bool.md) | Yes|True, if some stickers was added to the photo|
|thumb|[photoSize](../types/photoSize.md) | Yes|Video thumb as defined by sender, nullable|
|video|[file](../types/file.md) | Yes|File with the video|



### Type: [Video](../types/Video.md)


