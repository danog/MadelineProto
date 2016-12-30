---
title: updateReadMessagesContents
description: updateReadMessagesContents attributes, type and example
---
## Constructor: updateReadMessagesContents  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|:-------------:|---------:|
|messages|Array of [int](../types/int.md) | Required|
|pts|[int](../types/int.md) | Required|
|pts\_count|[int](../types/int.md) | Required|



### Type: [Update](../types/Update.md)


### Example:

```
$updateReadMessagesContents = ['_' => updateReadMessagesContents, 'messages' => [Vector t], 'pts' => int, 'pts_count' => int, ];
```