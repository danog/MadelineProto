---
title: updateDeleteChannelMessages
description: updateDeleteChannelMessages attributes, type and example
---
## Constructor: updateDeleteChannelMessages  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|:-------------:|---------:|
|channel\_id|[int](../types/int.md) | Required|
|messages|Array of [int](../types/int.md) | Required|
|channel\_pts|[int](../types/int.md) | Required|
|channel\_pts\_count|[int](../types/int.md) | Required|



### Type: [Update](../types/Update.md)


### Example:

```
$updateDeleteChannelMessages = ['_' => 'updateDeleteChannelMessages', 'channel_id' => int, 'messages' => [Vector t], 'channel_pts' => int, 'channel_pts_count' => int, ];
```  

