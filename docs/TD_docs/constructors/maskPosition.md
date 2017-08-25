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
|point|[int](../types/int.md) | Yes|Part of a photo relative to which the mask should be placed. 0 - forehead, 1 - eyes, 2 - mouth, 3 - chin|
|x\_shift|[double](../types/double.md) | Yes|Shift by X-axis in pixels, from left to right|
|y\_shift|[double](../types/double.md) | Yes|Shift by Y-axis in pixels, from top to bottom|
|zoom|[double](../types/double.md) | Yes|Mask zoom coefficient|



### Type: [MaskPosition](../types/MaskPosition.md)


