---
title: inputMessageVideo
description: Video message
---
## Constructor: inputMessageVideo  
[Back to constructors index](index.md)



Video message

### Attributes:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|video|[InputFile](../types/InputFile.md) | Yes|Video to send|
|thumb|[InputThumb](../types/InputThumb.md) | Yes|Video thumb, if available|
|added\_sticker\_file\_ids|Array of [int](../constructors/int.md) | Yes|File identifiers of stickers added onto the video|
|duration|[int](../types/int.md) | Yes|Duration of the video in seconds|
|width|[int](../types/int.md) | Yes|Video width|
|height|[int](../types/int.md) | Yes|Video height|
|caption|[string](../types/string.md) | Yes|Video caption, 0-200 characters|
|ttl|[int](../types/int.md) | Yes|Video TTL in seconds, 0-60. Non-zero TTL can be only specified in private chats|



### Type: [InputMessageContent](../types/InputMessageContent.md)


