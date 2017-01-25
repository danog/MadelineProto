---
title: updateEditChannelMessage
description: updateEditChannelMessage attributes, type and example
---
## Constructor: updateEditChannelMessage  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|:-------------:|---------:|
|message|[Message](../types/Message.md) | Required|
|pts|[int](../types/int.md) | Required|
|pts\_count|[int](../types/int.md) | Required|



### Type: [Update](../types/Update.md)


### Example:

```
$updateEditChannelMessage = ['_' => 'updateEditChannelMessage', 'message' => Message, 'pts' => int, 'pts_count' => int, ];
```  

