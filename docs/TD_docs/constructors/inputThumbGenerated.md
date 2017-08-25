---
title: inputThumbGenerated
description: Generated thumb, should be less than 200KB
---
## Constructor: inputThumbGenerated  
[Back to constructors index](index.md)



Generated thumb, should be less than 200KB

### Attributes:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|original\_path|[string](../types/string.md) | Yes|Path to the original file|
|conversion|[string](../types/string.md) | Yes|String specifying conversion applied to the original file, should be persistent across application restart|
|width|[int](../types/int.md) | Yes|Thumb width, usually shouldn't excceed 90. Use 0 if unknown|
|height|[int](../types/int.md) | Yes|Thumb height, usually shouldn't excceed 90. Use 0 if unknown|



### Type: [InputThumb](../types/InputThumb.md)


