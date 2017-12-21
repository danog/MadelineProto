---
title: inputThumb
description: Thumb to send along with a file, should be in jpeg format or webp format for stickers and less than 200KB in size
---
## Constructor: inputThumb  
[Back to constructors index](index.md)



Thumb to send along with a file, should be in jpeg format or webp format for stickers and less than 200KB in size

### Attributes:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|thumb|[InputFile](../types/InputFile.md) | Yes|Thumb file to send, sending thumbs by file_id is currently not supported|
|width|[int](../types/int.md) | Yes|Thumb width, usually shouldn't excceed 90. Use 0 if unknown|
|height|[int](../types/int.md) | Yes|Thumb height, usually shouldn't excceed 90. Use 0 if unknown|



### Type: [InputThumb](../types/InputThumb.md)


