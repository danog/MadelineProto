---
title: maskPosition
description: Position on a photo where a mask should be placed
---
## Constructor: maskPosition  
[Back to constructors index](index.md)



Position on a photo where a mask should be placed

### Attributes:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|point|[int](../types/int.md) | Yes|Part of a face relative to which the mask should be placed. 0 - forehead, 1 - eyes, 2 - mouth, 3 - chin|
|x\_shift|[double](../types/double.md) | Yes|Shift by X-axis measured in widths of the mask scaled to the face size, from left to right. For example, choosing -1.0 will place mask just to the left of the default mask position|
|y\_shift|[double](../types/double.md) | Yes|Shift by Y-axis measured in heights of the mask scaled to the face size, from top to bottom. For example, 1.0 will place the mask just below the default mask position.|
|scale|[double](../types/double.md) | Yes|Mask scaling coefficient. For example, 2.0 means double size|



### Type: [MaskPosition](../types/MaskPosition.md)


