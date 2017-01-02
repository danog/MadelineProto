---
title: updateDeleteChannelMessages
description: updateDeleteChannelMessages attributes, type and example
---
## Constructor: updateDeleteChannelMessages  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|:-------------:|---------:|
|peer|[Peer](../types/Peer.md) | Required|
|messages|Array of [int](../types/int.md) | Required|
|pts|[int](../types/int.md) | Required|
|pts\_count|[int](../types/int.md) | Required|



### Type: [Update](../types/Update.md)


### Example:

```
$updateDeleteChannelMessages = ['_' => 'updateDeleteChannelMessages', 'peer' => Peer, 'messages' => [Vector t], 'pts' => int, 'pts_count' => int, ];
```  

