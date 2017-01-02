---
title: updateNewChannelMessage
description: updateNewChannelMessage attributes, type and example
---
## Constructor: updateNewChannelMessage  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|:-------------:|---------:|
|message|[MTMessage](../types/MTMessage.md) | Required|
|channel\_pts|[int](../types/int.md) | Required|
|channel\_pts\_count|[int](../types/int.md) | Required|



### Type: [Update](../types/Update.md)


### Example:

```
$updateNewChannelMessage = ['_' => 'updateNewChannelMessage', 'message' => MTMessage, 'channel_pts' => int, 'channel_pts_count' => int, ];
```  

