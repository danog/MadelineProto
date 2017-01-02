---
title: updateNewMessage
description: updateNewMessage attributes, type and example
---
## Constructor: updateNewMessage  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|:-------------:|---------:|
|message|[MTMessage](../types/MTMessage.md) | Required|
|pts|[int](../types/int.md) | Required|
|pts\_count|[int](../types/int.md) | Required|



### Type: [Update](../types/Update.md)


### Example:

```
$updateNewMessage = ['_' => 'updateNewMessage', 'message' => MTMessage, 'pts' => int, 'pts_count' => int, ];
```